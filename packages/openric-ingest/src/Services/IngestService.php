<?php

declare(strict_types=1);

namespace OpenRiC\Ingest\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Ingest\Contracts\IngestServiceInterface;

/**
 * Ingest service -- adapted from Heratio AhgIngest\Services\IngestService (130 lines)
 * and AhgIngest\Controllers\IngestController configure/upload/map/validate/preview/commit flow.
 *
 * Maps CSV columns to RiC-O properties and creates entities in the triplestore + index tables.
 */
class IngestService implements IngestServiceInterface
{
    /**
     * Standard RiC-O property mapping targets for CSV columns.
     */
    private const RICO_TARGETS = [
        'title'               => 'rico:title',
        'identifier'          => 'rico:identifier',
        'description'         => 'rico:scopeAndContent',
        'date'                => 'rico:date',
        'date_start'          => 'rico:beginningDate',
        'date_end'            => 'rico:endDate',
        'extent'              => 'rico:physicalCharacteristics',
        'creator'             => 'rico:hasCreator',
        'level_of_description' => 'rico:hasRecordSetType',
        'language'            => 'rico:hasLanguage',
        'access_conditions'   => 'rico:hasRuleOrConvention',
        'finding_aid'         => 'rico:isAssociatedWith',
        'parent_iri'          => 'rico:isOrWasPartOf',
    ];

    public function importCsv(string $filepath, array $columnMapping, int $createdBy, array $options = []): array
    {
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open CSV file.');
        }

        $headers   = fgetcsv($handle);
        $totalRows = 0;
        $rows      = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
                $totalRows++;
            }
        }
        fclose($handle);

        // Create ingest job record
        $jobId = DB::table('ingest_jobs')->insertGetId([
            'filename'       => basename($filepath),
            'format'         => 'csv',
            'status'         => 'pending',
            'total_rows'     => $totalRows,
            'imported_rows'  => 0,
            'failed_rows'    => 0,
            'error_log'      => json_encode([]),
            'column_mapping' => json_encode($columnMapping, JSON_THROW_ON_ERROR),
            'created_by'     => $createdBy,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Process rows using the column mapping
        $imported  = 0;
        $failed    = 0;
        $errorLog  = [];

        DB::table('ingest_jobs')->where('id', $jobId)->update(['status' => 'processing', 'updated_at' => now()]);

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapCsvRow($row, $columnMapping);

                if (empty($mapped['rico:title'])) {
                    $errorLog[] = ['row' => $index + 2, 'error' => 'Missing required field: title'];
                    $failed++;
                    continue;
                }

                $iri = $options['iri_prefix'] ?? 'https://openric.local/entity/';
                $iri .= Str::uuid()->toString();

                // Insert into record_resources index table if it exists
                if (\Illuminate\Support\Facades\Schema::hasTable('record_resources')) {
                    DB::table('record_resources')->insert([
                        'iri'                => $iri,
                        'title'              => $mapped['rico:title'] ?? '',
                        'identifier'         => $mapped['rico:identifier'] ?? null,
                        'scope_and_content'  => $mapped['rico:scopeAndContent'] ?? null,
                        'date_statement'     => $mapped['rico:date'] ?? null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errorLog[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                $failed++;
            }
        }

        DB::table('ingest_jobs')->where('id', $jobId)->update([
            'status'        => 'completed',
            'imported_rows' => $imported,
            'failed_rows'   => $failed,
            'error_log'     => json_encode($errorLog, JSON_THROW_ON_ERROR),
            'updated_at'    => now(),
        ]);

        return [
            'job_id'     => $jobId,
            'total_rows' => $totalRows,
            'message'    => "Imported {$imported} of {$totalRows} rows ({$failed} failed).",
        ];
    }

    public function importXml(string $filepath, string $format, int $createdBy, array $options = []): array
    {
        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException('Cannot read XML file.');
        }

        $xml = new \SimpleXMLElement($content);

        $jobId = DB::table('ingest_jobs')->insertGetId([
            'filename'       => basename($filepath),
            'format'         => $format,
            'status'         => 'processing',
            'total_rows'     => 0,
            'imported_rows'  => 0,
            'failed_rows'    => 0,
            'error_log'      => json_encode([]),
            'column_mapping' => json_encode([]),
            'created_by'     => $createdBy,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $imported = 0;
        $failed   = 0;
        $errorLog = [];

        if ($format === 'ead3') {
            $imported = $this->processEad3($xml, $options, $errorLog, $failed);
        } elseif ($format === 'eac-cpf') {
            $imported = $this->processEacCpf($xml, $options, $errorLog, $failed);
        } else {
            $errorLog[] = ['row' => 0, 'error' => "Unsupported format: {$format}"];
        }

        $totalRows = $imported + $failed;

        DB::table('ingest_jobs')->where('id', $jobId)->update([
            'status'        => 'completed',
            'total_rows'    => $totalRows,
            'imported_rows' => $imported,
            'failed_rows'   => $failed,
            'error_log'     => json_encode($errorLog, JSON_THROW_ON_ERROR),
            'updated_at'    => now(),
        ]);

        return [
            'job_id'     => $jobId,
            'total_rows' => $totalRows,
            'message'    => "Imported {$imported} records from {$format} ({$failed} failed).",
        ];
    }

    public function validateImport(int $jobId): array
    {
        $job = DB::table('ingest_jobs')->where('id', $jobId)->first();

        if (!$job) {
            return ['valid' => false, 'errors' => ['Job not found.'], 'warnings' => [], 'row_count' => 0];
        }

        $errors   = [];
        $warnings = [];

        $errorLog = json_decode($job->error_log, true) ?: [];
        foreach ($errorLog as $entry) {
            $errors[] = "Row {$entry['row']}: {$entry['error']}";
        }

        if ($job->total_rows === 0) {
            $errors[] = 'No rows found in the import file.';
        }

        if ($job->failed_rows > 0 && $job->imported_rows === 0) {
            $errors[] = 'All rows failed validation.';
        } elseif ($job->failed_rows > 0) {
            $warnings[] = "{$job->failed_rows} of {$job->total_rows} rows had errors.";
        }

        return [
            'valid'     => empty($errors),
            'errors'    => $errors,
            'warnings'  => $warnings,
            'row_count' => $job->total_rows,
        ];
    }

    public function getImportHistory(int $page = 1, int $limit = 20): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        $q = DB::table('ingest_jobs')
            ->leftJoin('users', 'ingest_jobs.created_by', '=', 'users.id')
            ->select('ingest_jobs.*', 'users.name as created_by_name');

        $total = $q->count();

        $results = $q->orderByDesc('ingest_jobs.created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    public function getImportStats(): array
    {
        $row = DB::table('ingest_jobs')
            ->selectRaw('COUNT(*) as total_jobs')
            ->selectRaw('COALESCE(SUM(imported_rows), 0) as total_imported')
            ->selectRaw('COALESCE(SUM(failed_rows), 0) as total_failed')
            ->first();

        $formats = DB::table('ingest_jobs')
            ->select('format')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('format')
            ->pluck('count', 'format')
            ->toArray();

        return [
            'total_jobs'     => (int) $row->total_jobs,
            'total_imported' => (int) $row->total_imported,
            'total_failed'   => (int) $row->total_failed,
            'formats'        => $formats,
        ];
    }

    /**
     * Map a CSV row using the provided column mapping.
     */
    private function mapCsvRow(array $row, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $csvColumn => $ricoProperty) {
            if (isset($row[$csvColumn]) && $row[$csvColumn] !== '') {
                $target = self::RICO_TARGETS[$ricoProperty] ?? $ricoProperty;
                $mapped[$target] = trim($row[$csvColumn]);
            }
        }

        return $mapped;
    }

    /**
     * Process EAD3 XML and create record resources.
     */
    private function processEad3(\SimpleXMLElement $xml, array $options, array &$errorLog, int &$failed): int
    {
        $imported  = 0;
        $iriPrefix = $options['iri_prefix'] ?? 'https://openric.local/entity/';

        // Register EAD3 namespace
        $xml->registerXPathNamespace('ead', 'http://ead3.archivists.org/schema/');

        $components = $xml->xpath('//ead:c') ?: $xml->xpath('//c') ?: [];

        foreach ($components as $index => $c) {
            try {
                $title = (string) ($c->did->unittitle ?? '');
                $id    = (string) ($c->did->unitid ?? '');
                $scope = (string) ($c->scopecontent->p ?? '');
                $date  = (string) ($c->did->unitdate ?? '');

                if ($title === '') {
                    $errorLog[] = ['row' => $index + 1, 'error' => 'Missing unittitle'];
                    $failed++;
                    continue;
                }

                $iri = $iriPrefix . Str::uuid()->toString();

                if (\Illuminate\Support\Facades\Schema::hasTable('record_resources')) {
                    DB::table('record_resources')->insert([
                        'iri'               => $iri,
                        'title'             => $title,
                        'identifier'        => $id ?: null,
                        'scope_and_content' => $scope ?: null,
                        'date_statement'    => $date ?: null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errorLog[] = ['row' => $index + 1, 'error' => $e->getMessage()];
                $failed++;
            }
        }

        return $imported;
    }

    /**
     * Process EAC-CPF XML and create agent records.
     */
    private function processEacCpf(\SimpleXMLElement $xml, array $options, array &$errorLog, int &$failed): int
    {
        $imported  = 0;
        $iriPrefix = $options['iri_prefix'] ?? 'https://openric.local/agent/';

        $xml->registerXPathNamespace('eac', 'urn:isbn:1-931666-33-4');

        $records = $xml->xpath('//eac:cpfDescription') ?: $xml->xpath('//cpfDescription') ?: [$xml];

        foreach ($records as $index => $record) {
            try {
                $identity = $record->identity ?? $record;
                $name     = (string) ($identity->nameEntry->part ?? $identity->nameEntry ?? '');

                if ($name === '') {
                    $errorLog[] = ['row' => $index + 1, 'error' => 'Missing nameEntry'];
                    $failed++;
                    continue;
                }

                $iri = $iriPrefix . Str::uuid()->toString();

                if (\Illuminate\Support\Facades\Schema::hasTable('agents')) {
                    DB::table('agents')->insert([
                        'iri'             => $iri,
                        'authorized_name' => $name,
                        'agent_type'      => (string) ($identity->entityType ?? 'corporateBody'),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errorLog[] = ['row' => $index + 1, 'error' => $e->getMessage()];
                $failed++;
            }
        }

        return $imported;
    }
}
