<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenRiC\DataMigration\Contracts\DataMigrationServiceInterface;

/**
 * Data migration service — adapted from Heratio ahg-data-migration DataMigrationService + DataMigrationController.
 *
 * Handles CSV analysis, column mapping, row transformation, validation, batch import,
 * preset management, import history, and rollback.
 */
class DataMigrationService implements DataMigrationServiceInterface
{
    /**
     * Target field definitions by entity type — maps field key => human label.
     */
    private const TARGET_FIELDS = [
        'record' => [
            'title' => 'Title',
            'identifier' => 'Identifier',
            'description' => 'Description',
            'scope_and_content' => 'Scope and Content',
            'date_start' => 'Start Date',
            'date_end' => 'End Date',
            'date_expression' => 'Date Expression',
            'extent' => 'Extent',
            'level_of_description' => 'Level of Description',
            'arrangement' => 'Arrangement',
            'access_conditions' => 'Access Conditions',
            'reproduction_conditions' => 'Reproduction Conditions',
            'physical_characteristics' => 'Physical Characteristics',
            'finding_aids' => 'Finding Aids',
            'archival_history' => 'Archival History',
            'acquisition' => 'Acquisition',
            'appraisal' => 'Appraisal',
            'accruals' => 'Accruals',
            'location_of_originals' => 'Location of Originals',
            'location_of_copies' => 'Location of Copies',
            'related_units' => 'Related Units of Description',
            'rules' => 'Rules or Conventions',
            'sources' => 'Sources',
            'parent_identifier' => 'Parent Identifier',
            'repository_identifier' => 'Repository Identifier',
            'language' => 'Language',
            'legacy_id' => 'Legacy ID',
            'parent_legacy_id' => 'Parent Legacy ID',
        ],
        'agent' => [
            'name' => 'Authorized Name',
            'type' => 'Agent Type (person/corporate/family)',
            'dates_of_existence' => 'Dates of Existence',
            'history' => 'History/Biography',
            'places' => 'Places',
            'legal_status' => 'Legal Status',
            'functions' => 'Functions',
            'mandates' => 'Mandates',
            'internal_structures' => 'Internal Structures',
            'general_context' => 'General Context',
            'identifier' => 'Identifier',
            'legacy_id' => 'Legacy ID',
        ],
        'accession' => [
            'identifier' => 'Accession Number',
            'title' => 'Title',
            'date' => 'Date',
            'source_of_acquisition' => 'Source of Acquisition',
            'scope_and_content' => 'Scope and Content',
            'archival_history' => 'Archival History',
            'location_information' => 'Location Information',
            'physical_characteristics' => 'Physical Characteristics',
            'received_extent_units' => 'Received Extent Units',
            'processing_notes' => 'Processing Notes',
            'legacy_id' => 'Legacy ID',
        ],
    ];

    // =========================================================================
    // CSV Analysis
    // =========================================================================

    public function analyzeCsv(string $filePath, int $previewRows = 10): array
    {
        $result = ['headers' => [], 'totalRows' => 0, 'rows' => [], 'columnTypes' => []];

        $fullPath = Storage::path($filePath);
        if (!file_exists($fullPath)) {
            return $result;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            return $result;
        }

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $result;
        }

        $headers = array_map('trim', $headers);
        $result['headers'] = $headers;

        // Sample rows for type detection
        $sampleValues = array_fill_keys($headers, []);
        $rowCount = 0;

        while (($csvRow = fgetcsv($handle)) !== false) {
            $rowCount++;
            while (count($csvRow) < count($headers)) {
                $csvRow[] = '';
            }
            $assoc = array_combine($headers, array_slice($csvRow, 0, count($headers)));

            if ($rowCount <= $previewRows) {
                $result['rows'][] = $assoc;
            }

            // Collect first 50 values for type detection
            if ($rowCount <= 50) {
                foreach ($headers as $h) {
                    $sampleValues[$h][] = $assoc[$h] ?? '';
                }
            }
        }

        $result['totalRows'] = $rowCount;
        fclose($handle);

        // Detect column types
        foreach ($headers as $header) {
            $result['columnTypes'][$header] = $this->detectColumnType($sampleValues[$header] ?? []);
        }

        return $result;
    }

    /**
     * Detect the likely type of a column from sample values.
     */
    private function detectColumnType(array $values): string
    {
        $nonEmpty = array_filter($values, fn (string $v): bool => $v !== '');
        if (empty($nonEmpty)) {
            return 'text';
        }

        $dateCount = 0;
        $numericCount = 0;
        $total = count($nonEmpty);

        foreach ($nonEmpty as $val) {
            if (is_numeric($val)) {
                $numericCount++;
            }
            if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $val) || strtotime($val) !== false) {
                $dateCount++;
            }
        }

        if ($numericCount / $total > 0.8) {
            return 'numeric';
        }
        if ($dateCount / $total > 0.8) {
            return 'date';
        }

        return 'text';
    }

    // =========================================================================
    // Column Mapping
    // =========================================================================

    public function mapColumns(array $columnMapping, array $rows): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            $mappedRow = [];
            foreach ($columnMapping as $sourceCol => $targetField) {
                if ($targetField !== '' && array_key_exists($sourceCol, $row)) {
                    $mappedRow[$targetField] = $row[$sourceCol];
                }
            }
            $mapped[] = $mappedRow;
        }
        return $mapped;
    }

    // =========================================================================
    // Row Transformation
    // =========================================================================

    public function transformRow(array $row, array $transformRules): array
    {
        foreach ($transformRules as $field => $rule) {
            if (!array_key_exists($field, $row)) {
                // Apply default value if field missing
                if (($rule['type'] ?? '') === 'default' && isset($rule['value'])) {
                    $row[$field] = $rule['value'];
                }
                continue;
            }

            $value = $row[$field];
            $type = $rule['type'] ?? 'trim';

            $row[$field] = match ($type) {
                'trim' => trim((string) $value),
                'uppercase' => mb_strtoupper(trim((string) $value)),
                'lowercase' => mb_strtolower(trim((string) $value)),
                'titlecase' => mb_convert_case(trim((string) $value), MB_CASE_TITLE),
                'date_format' => $this->transformDate((string) $value, $rule['from_format'] ?? 'Y-m-d', $rule['to_format'] ?? 'Y-m-d'),
                'regex_replace' => preg_replace($rule['pattern'] ?? '//', $rule['replacement'] ?? '', (string) $value) ?? $value,
                'prefix' => ($rule['prefix'] ?? '') . trim((string) $value),
                'suffix' => trim((string) $value) . ($rule['suffix'] ?? ''),
                'default' => ($value === '' || $value === null) ? ($rule['value'] ?? '') : $value,
                'strip_html' => strip_tags((string) $value),
                'truncate' => mb_substr(trim((string) $value), 0, (int) ($rule['length'] ?? 255)),
                'split' => explode($rule['delimiter'] ?? '|', (string) $value),
                default => $value,
            };
        }

        return $row;
    }

    /**
     * Transform a date string from one format to another.
     */
    private function transformDate(string $value, string $fromFormat, string $toFormat): string
    {
        if ($value === '') {
            return '';
        }
        $date = \DateTime::createFromFormat($fromFormat, $value);
        if ($date === false) {
            // Try strtotime fallback
            $ts = strtotime($value);
            if ($ts !== false) {
                return date($toFormat, $ts);
            }
            return $value;
        }
        return $date->format($toFormat);
    }

    // =========================================================================
    // Row Validation
    // =========================================================================

    public function validateRow(array $row, string $targetEntityType): array
    {
        $errors = [];
        $fields = self::TARGET_FIELDS[$targetEntityType] ?? [];

        if (empty($fields)) {
            return ['valid' => false, 'errors' => ["Unknown entity type: {$targetEntityType}"]];
        }

        // Required field checks by entity type
        $required = match ($targetEntityType) {
            'record' => ['title'],
            'agent' => ['name'],
            'accession' => ['identifier'],
            default => [],
        };

        foreach ($required as $field) {
            if (empty($row[$field]) || trim((string) $row[$field]) === '') {
                $label = $fields[$field] ?? $field;
                $errors[] = "Required field '{$label}' is empty.";
            }
        }

        // Date validation
        $dateFields = ['date_start', 'date_end', 'date'];
        foreach ($dateFields as $df) {
            if (!empty($row[$df]) && strtotime((string) $row[$df]) === false) {
                $errors[] = "Field '{$df}' contains an invalid date: {$row[$df]}";
            }
        }

        // Identifier uniqueness check for records
        if ($targetEntityType === 'record' && !empty($row['identifier'])) {
            $exists = DB::table('records')->where('identifier', $row['identifier'])->exists();
            if ($exists) {
                $errors[] = "Record with identifier '{$row['identifier']}' already exists.";
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    // =========================================================================
    // Batch Import
    // =========================================================================

    public function importBatch(array $rows, string $targetEntityType, int $jobId): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $legacyIdMap = [];
        $createdEntityIds = [];

        foreach ($rows as $index => $row) {
            try {
                $validation = $this->validateRow($row, $targetEntityType);
                if (!$validation['valid']) {
                    $errors[] = ['row' => $index + 1, 'messages' => $validation['errors']];
                    $skipped++;
                    continue;
                }

                DB::beginTransaction();

                $entityId = $this->createEntity($targetEntityType, $row, $legacyIdMap);

                if ($entityId > 0) {
                    if (!empty($row['legacy_id'])) {
                        $legacyIdMap[$row['legacy_id']] = $entityId;
                    }
                    $createdEntityIds[] = $entityId;
                    $imported++;
                } else {
                    $skipped++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[] = ['row' => $index + 1, 'messages' => [$e->getMessage()]];
            }

            // Update job progress every 25 rows
            if (($index + 1) % 25 === 0) {
                $this->updateJobProgress($jobId, [
                    'processed_rows' => $index + 1,
                    'status' => 'processing',
                ]);
            }
        }

        // Store rollback data
        if (!empty($createdEntityIds)) {
            DB::table('data_migration_jobs')->where('id', $jobId)->update([
                'rollback_data' => json_encode([
                    'entity_type' => $targetEntityType,
                    'entity_ids' => $createdEntityIds,
                ]),
            ]);
        }

        // Final progress
        $this->updateJobProgress($jobId, [
            'status' => empty($errors) && $imported === 0 ? 'failed' : 'completed',
            'processed_rows' => count($rows),
            'error_rows' => count($errors),
            'error_log' => $errors,
        ]);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Create a single entity from mapped/transformed row data.
     */
    private function createEntity(string $entityType, array $data, array &$legacyIdMap): int
    {
        $userId = Auth::id();
        $now = now();

        return match ($entityType) {
            'record' => $this->createRecord($data, $legacyIdMap, $userId, $now),
            'agent' => $this->createAgent($data, $userId, $now),
            'accession' => $this->createAccession($data, $userId, $now),
            default => 0,
        };
    }

    private function createRecord(array $data, array &$legacyIdMap, ?int $userId, $now): int
    {
        // Resolve parent from legacy ID
        $parentId = null;
        if (!empty($data['parent_legacy_id']) && isset($legacyIdMap[$data['parent_legacy_id']])) {
            $parentId = $legacyIdMap[$data['parent_legacy_id']];
        }

        $iri = 'urn:openric:record:' . Str::uuid()->toString();

        $id = (int) DB::table('records')->insertGetId([
            'iri' => $iri,
            'identifier' => $data['identifier'] ?? null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? $data['scope_and_content'] ?? null,
            'scope_and_content' => $data['scope_and_content'] ?? null,
            'date_start' => !empty($data['date_start']) ? $data['date_start'] : null,
            'date_end' => !empty($data['date_end']) ? $data['date_end'] : null,
            'date_expression' => $data['date_expression'] ?? null,
            'extent' => $data['extent'] ?? null,
            'level_of_description' => $data['level_of_description'] ?? null,
            'arrangement' => $data['arrangement'] ?? null,
            'access_conditions' => $data['access_conditions'] ?? null,
            'reproduction_conditions' => $data['reproduction_conditions'] ?? null,
            'physical_characteristics' => $data['physical_characteristics'] ?? null,
            'finding_aids' => $data['finding_aids'] ?? null,
            'archival_history' => $data['archival_history'] ?? null,
            'acquisition' => $data['acquisition'] ?? null,
            'appraisal' => $data['appraisal'] ?? null,
            'accruals' => $data['accruals'] ?? null,
            'location_of_originals' => $data['location_of_originals'] ?? null,
            'location_of_copies' => $data['location_of_copies'] ?? null,
            'related_units' => $data['related_units'] ?? null,
            'rules' => $data['rules'] ?? null,
            'sources' => $data['sources'] ?? null,
            'parent_id' => $parentId,
            'language' => $data['language'] ?? 'en',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    private function createAgent(array $data, ?int $userId, $now): int
    {
        $iri = 'urn:openric:agent:' . Str::uuid()->toString();

        return (int) DB::table('agents')->insertGetId([
            'iri' => $iri,
            'name' => $data['name'] ?? '',
            'type' => $data['type'] ?? 'person',
            'dates_of_existence' => $data['dates_of_existence'] ?? null,
            'history' => $data['history'] ?? null,
            'places' => $data['places'] ?? null,
            'legal_status' => $data['legal_status'] ?? null,
            'functions' => $data['functions'] ?? null,
            'mandates' => $data['mandates'] ?? null,
            'internal_structures' => $data['internal_structures'] ?? null,
            'general_context' => $data['general_context'] ?? null,
            'identifier' => $data['identifier'] ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createAccession(array $data, ?int $userId, $now): int
    {
        return (int) DB::table('accessions')->insertGetId([
            'identifier' => $data['identifier'] ?? '',
            'title' => $data['title'] ?? null,
            'date' => !empty($data['date']) ? $data['date'] : null,
            'source_of_acquisition' => $data['source_of_acquisition'] ?? null,
            'scope_and_content' => $data['scope_and_content'] ?? null,
            'archival_history' => $data['archival_history'] ?? null,
            'location_information' => $data['location_information'] ?? null,
            'physical_characteristics' => $data['physical_characteristics'] ?? null,
            'received_extent_units' => $data['received_extent_units'] ?? null,
            'processing_notes' => $data['processing_notes'] ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // =========================================================================
    // Job Progress
    // =========================================================================

    private function updateJobProgress(int $jobId, array $data): void
    {
        $update = [];
        $allowed = ['status', 'processed_rows', 'error_rows', 'total_rows'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }
        if (isset($data['error_log'])) {
            $update['error_log'] = json_encode($data['error_log']);
        }
        if (!empty($update)) {
            $update['updated_at'] = now();
            DB::table('data_migration_jobs')->where('id', $jobId)->update($update);
        }
    }

    // =========================================================================
    // Presets
    // =========================================================================

    public function getFieldMappingPresets(): array
    {
        return DB::table('data_migration_presets')
            ->select('id', 'name', 'entity_type', 'column_mapping', 'transform_rules', 'created_by', 'created_at', 'updated_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($row): array {
                $arr = (array) $row;
                $arr['column_mapping'] = json_decode((string) ($arr['column_mapping'] ?? '{}'), true);
                $arr['transform_rules'] = json_decode((string) ($arr['transform_rules'] ?? '{}'), true);
                return $arr;
            })
            ->toArray();
    }

    public function saveFieldMappingPreset(string $name, string $entityType, array $columnMapping, array $transformRules): int
    {
        return (int) DB::table('data_migration_presets')->insertGetId([
            'name' => $name,
            'entity_type' => $entityType,
            'column_mapping' => json_encode($columnMapping),
            'transform_rules' => json_encode($transformRules),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // History
    // =========================================================================

    public function getImportHistory(int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('data_migration_jobs')
            ->leftJoin('users', 'data_migration_jobs.created_by', '=', 'users.id')
            ->select(
                'data_migration_jobs.*',
                'users.username as created_by_name'
            )
            ->orderByDesc('data_migration_jobs.created_at');

        $total = $query->count();
        $jobs = $query->offset($offset)->limit($limit)->get()->map(function ($row): array {
            $arr = (array) $row;
            $arr['column_mapping'] = json_decode((string) ($arr['column_mapping'] ?? '{}'), true);
            $arr['error_log'] = json_decode((string) ($arr['error_log'] ?? '[]'), true);
            return $arr;
        })->toArray();

        return ['jobs' => $jobs, 'total' => $total];
    }

    // =========================================================================
    // Rollback
    // =========================================================================

    public function rollbackImport(int $jobId): array
    {
        $job = DB::table('data_migration_jobs')->where('id', $jobId)->first();
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found.'];
        }

        $rollbackData = json_decode((string) ($job->rollback_data ?? ''), true);
        if (empty($rollbackData) || empty($rollbackData['entity_ids'])) {
            return ['success' => false, 'message' => 'No rollback data available for this job.'];
        }

        $entityType = $rollbackData['entity_type'] ?? '';
        $entityIds = $rollbackData['entity_ids'] ?? [];

        $table = match ($entityType) {
            'record' => 'records',
            'agent' => 'agents',
            'accession' => 'accessions',
            default => null,
        };

        if (!$table) {
            return ['success' => false, 'message' => "Unknown entity type: {$entityType}"];
        }

        $deleted = 0;
        DB::beginTransaction();
        try {
            foreach (array_chunk($entityIds, 100) as $chunk) {
                $deleted += DB::table($table)->whereIn('id', $chunk)->delete();
            }

            DB::table('data_migration_jobs')->where('id', $jobId)->update([
                'status' => 'rolled_back',
                'rollback_data' => null,
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("DataMigration rollback failed for job {$jobId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Rollback failed: ' . $e->getMessage()];
        }

        return ['success' => true, 'deleted' => $deleted, 'message' => "Rolled back {$deleted} entities."];
    }

    // =========================================================================
    // Target Fields Lookup
    // =========================================================================

    /**
     * Get available target fields for a given entity type.
     *
     * @return array<string, string> field_key => label
     */
    public function getTargetFields(string $entityType): array
    {
        return self::TARGET_FIELDS[$entityType] ?? [];
    }

    /**
     * Create a migration job record.
     */
    public function createJob(string $sourceFile, string $sourceFormat, string $targetEntityType, array $columnMapping, array $transformRules, int $totalRows): int
    {
        return (int) DB::table('data_migration_jobs')->insertGetId([
            'source_file' => $sourceFile,
            'source_format' => $sourceFormat,
            'target_entity_type' => $targetEntityType,
            'column_mapping' => json_encode($columnMapping),
            'transform_rules' => json_encode($transformRules),
            'status' => 'pending',
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'error_rows' => 0,
            'error_log' => json_encode([]),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get a single job by ID.
     */
    public function getJob(int $jobId): ?array
    {
        $row = DB::table('data_migration_jobs')->where('id', $jobId)->first();
        if (!$row) {
            return null;
        }
        $arr = (array) $row;
        $arr['column_mapping'] = json_decode((string) ($arr['column_mapping'] ?? '{}'), true);
        $arr['transform_rules'] = json_decode((string) ($arr['transform_rules'] ?? '{}'), true);
        $arr['error_log'] = json_decode((string) ($arr['error_log'] ?? '[]'), true);
        $arr['rollback_data'] = json_decode((string) ($arr['rollback_data'] ?? ''), true);
        return $arr;
    }
}
