<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenRiC\DataMigration\Contracts\DataMigrationServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data migration service — adapted from Heratio ahg-data-migration DataMigrationService (1,169 lines).
 *
 * Handles CSV analysis, column mapping, row transformation, validation, batch import,
 * batch export, preset management, import history, and rollback.
 * Uses PostgreSQL ILIKE for case-insensitive matching.
 */
class DataMigrationService implements DataMigrationServiceInterface
{
    /**
     * Target field definitions by entity type — maps field key => human label.
     * Adapted from Heratio DataMigrationService::getTargetFields().
     */
    private const TARGET_FIELDS = [
        'record' => [
            'title'                        => 'Title',
            'identifier'                   => 'Identifier',
            'alternate_title'              => 'Alternate Title',
            'description'                  => 'Description',
            'scope_and_content'            => 'Scope and Content',
            'date_start'                   => 'Start Date',
            'date_end'                     => 'End Date',
            'date_expression'              => 'Date Expression',
            'extent'                       => 'Extent and Medium',
            'level_of_description'         => 'Level of Description',
            'arrangement'                  => 'Arrangement',
            'access_conditions'            => 'Conditions Governing Access',
            'reproduction_conditions'      => 'Conditions Governing Reproduction',
            'physical_characteristics'     => 'Physical Characteristics',
            'finding_aids'                 => 'Finding Aids',
            'archival_history'             => 'Archival History',
            'acquisition'                  => 'Immediate Source of Acquisition',
            'appraisal'                    => 'Appraisal',
            'accruals'                     => 'Accruals',
            'location_of_originals'        => 'Location of Originals',
            'location_of_copies'           => 'Location of Copies',
            'related_units'                => 'Related Units of Description',
            'rules'                        => 'Rules or Conventions',
            'sources'                      => 'Sources',
            'revision_history'             => 'Revision History',
            'parent_identifier'            => 'Parent Identifier',
            'repository_identifier'        => 'Repository Identifier',
            'description_identifier'       => 'Description Identifier',
            'source_standard'              => 'Source Standard',
            'language'                     => 'Language',
            'legacy_id'                    => 'Legacy ID',
            'parent_legacy_id'             => 'Parent Legacy ID',
        ],
        'agent' => [
            'name'                         => 'Authorized Name',
            'type'                         => 'Agent Type (person/corporate/family)',
            'dates_of_existence'           => 'Dates of Existence',
            'history'                      => 'History/Biography',
            'places'                       => 'Places',
            'legal_status'                 => 'Legal Status',
            'functions'                    => 'Functions',
            'mandates'                     => 'Mandates',
            'internal_structures'          => 'Internal Structures',
            'general_context'              => 'General Context',
            'institution_responsible_identifier' => 'Institution Responsible Identifier',
            'rules'                        => 'Rules or Conventions',
            'sources'                      => 'Sources',
            'revision_history'             => 'Revision History',
            'identifier'                   => 'Identifier',
            'description_identifier'       => 'Description Identifier',
            'legacy_id'                    => 'Legacy ID',
        ],
        'accession' => [
            'identifier'                   => 'Accession Number',
            'title'                        => 'Title',
            'date'                         => 'Date',
            'source_of_acquisition'        => 'Source of Acquisition',
            'scope_and_content'            => 'Scope and Content',
            'archival_history'             => 'Archival History',
            'appraisal'                    => 'Appraisal',
            'location_information'         => 'Location Information',
            'physical_characteristics'     => 'Physical Characteristics',
            'received_extent_units'        => 'Received Extent Units',
            'processing_notes'             => 'Processing Notes',
            'acquisition_type'             => 'Acquisition Type',
            'processing_priority'          => 'Processing Priority',
            'processing_status'            => 'Processing Status',
            'resource_type'                => 'Resource Type',
            'legacy_id'                    => 'Legacy ID',
        ],
        'repository' => [
            'name'                         => 'Authorized Name',
            'dates_of_existence'           => 'Dates of Existence',
            'history'                      => 'History',
            'places'                       => 'Places',
            'legal_status'                 => 'Legal Status',
            'functions'                    => 'Functions',
            'mandates'                     => 'Mandates',
            'internal_structures'          => 'Internal Structures',
            'general_context'              => 'General Context',
            'geocultural_context'          => 'Geocultural Context',
            'collecting_policies'          => 'Collecting Policies',
            'buildings'                    => 'Buildings',
            'holdings'                     => 'Holdings',
            'finding_aids'                 => 'Finding Aids',
            'opening_times'                => 'Opening Times',
            'access_conditions'            => 'Access Conditions',
            'disabled_access'              => 'Disabled Access',
            'research_services'            => 'Research Services',
            'reproduction_services'        => 'Reproduction Services',
            'public_facilities'            => 'Public Facilities',
            'legacy_id'                    => 'Legacy ID',
        ],
    ];

    // =========================================================================
    // CSV Analysis — adapted from Heratio DataMigrationService::parseCSV()
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

        // Skip BOM — adapted from Heratio
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
    // Column Mapping — adapted from Heratio controller mapping logic
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
    // Row Transformation — adapted from Heratio patterns
    // =========================================================================

    public function transformRow(array $row, array $transformRules): array
    {
        foreach ($transformRules as $field => $rule) {
            if (!array_key_exists($field, $row)) {
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
                'concat' => trim((string) $value) . ($rule['separator'] ?? ' ') . ($rule['append_field'] ?? ''),
                'map_value' => $rule['map'][(string) $value] ?? $value,
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
            $ts = strtotime($value);
            if ($ts !== false) {
                return date($toFormat, $ts);
            }
            return $value;
        }
        return $date->format($toFormat);
    }

    // =========================================================================
    // Row Validation — adapted from Heratio patterns
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
            'repository' => ['name'],
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

        // Identifier uniqueness check for records (PostgreSQL ILIKE)
        if ($targetEntityType === 'record' && !empty($row['identifier'])) {
            $exists = DB::table('records')
                ->whereRaw('identifier ILIKE ?', [$row['identifier']])
                ->exists();
            if ($exists) {
                $errors[] = "Record with identifier '{$row['identifier']}' already exists.";
            }
        }

        // Agent name uniqueness check (PostgreSQL ILIKE)
        if ($targetEntityType === 'agent' && !empty($row['name'])) {
            $exists = DB::table('agents')
                ->whereRaw('name ILIKE ?', [$row['name']])
                ->exists();
            if ($exists) {
                $errors[] = "Agent with name '{$row['name']}' already exists.";
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    // =========================================================================
    // Validate Import File — adapted from Heratio validateImport
    // =========================================================================

    public function validateImportFile(string $filePath, string $targetType, array $mappings): array
    {
        $errors = [];
        $warnings = [];

        $analysis = $this->analyzeCsv($filePath, 100);

        if (empty($analysis['headers'])) {
            return ['valid' => false, 'errors' => ['File is empty or cannot be parsed.'], 'warnings' => [], 'totalRows' => 0];
        }

        if ($analysis['totalRows'] === 0) {
            return ['valid' => false, 'errors' => ['File contains no data rows.'], 'warnings' => [], 'totalRows' => 0];
        }

        $targetFields = $this->getTargetFields($targetType);
        if (empty($targetFields)) {
            return ['valid' => false, 'errors' => ["Unknown target type: {$targetType}"], 'warnings' => [], 'totalRows' => $analysis['totalRows']];
        }

        // Check that mapped source columns exist in the file
        foreach ($mappings as $sourceCol => $targetField) {
            if (!in_array($sourceCol, $analysis['headers'], true)) {
                $errors[] = "Mapped source column '{$sourceCol}' not found in file headers.";
            }
            if (!isset($targetFields[$targetField])) {
                $warnings[] = "Target field '{$targetField}' is not a standard field for type '{$targetType}'.";
            }
        }

        // Check required fields are mapped
        $required = match ($targetType) {
            'record' => ['title'],
            'agent' => ['name'],
            'accession' => ['identifier'],
            'repository' => ['name'],
            default => [],
        };

        $mappedTargets = array_values($mappings);
        foreach ($required as $reqField) {
            if (!in_array($reqField, $mappedTargets, true)) {
                $errors[] = "Required target field '{$reqField}' is not mapped to any source column.";
            }
        }

        // Validate first N rows
        $sampleErrors = 0;
        foreach (array_slice($analysis['rows'], 0, 20) as $idx => $row) {
            $mapped = [];
            foreach ($mappings as $sourceCol => $targetField) {
                if (isset($row[$sourceCol])) {
                    $mapped[$targetField] = $row[$sourceCol];
                }
            }
            $validation = $this->validateRow($mapped, $targetType);
            if (!$validation['valid']) {
                $sampleErrors++;
                if ($sampleErrors <= 5) {
                    foreach ($validation['errors'] as $err) {
                        $warnings[] = "Row " . ($idx + 1) . ": " . $err;
                    }
                }
            }
        }

        if ($sampleErrors > 5) {
            $warnings[] = "... and " . ($sampleErrors - 5) . " more rows with validation issues in the sample.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'totalRows' => $analysis['totalRows'],
        ];
    }

    // =========================================================================
    // Batch Import — adapted from Heratio DataMigrationService::executeImport()
    // =========================================================================

    public function importBatch(array $rows, string $targetEntityType, int $jobId, array $options = []): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $legacyIdMap = [];
        $createdEntityIds = [];
        $importType = $options['import_type'] ?? 'create';
        $skipErrors = (bool) ($options['skip_errors'] ?? true);

        $this->updateJobProgress($jobId, [
            'status' => 'processing',
            'progress_message' => 'Starting import...',
        ]);

        foreach ($rows as $index => $row) {
            try {
                $validation = $this->validateRow($row, $targetEntityType);
                if (!$validation['valid'] && $importType === 'create') {
                    $errors[] = ['row' => $index + 1, 'messages' => $validation['errors']];
                    $skipped++;
                    if (!$skipErrors) {
                        break;
                    }
                    continue;
                }

                DB::beginTransaction();

                $entityId = $this->createEntity($targetEntityType, $row, $legacyIdMap, $importType);

                if ($entityId > 0) {
                    if (!empty($row['legacy_id'])) {
                        $legacyIdMap[$row['legacy_id']] = $entityId;
                    }
                    $createdEntityIds[] = $entityId;
                    if ($importType === 'update') {
                        $updated++;
                    } else {
                        $imported++;
                    }
                } else {
                    $skipped++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[] = ['row' => $index + 1, 'messages' => [$e->getMessage()]];
                if (!$skipErrors) {
                    break;
                }
            }

            // Update job progress every 25 rows — adapted from Heratio
            if (($index + 1) % 25 === 0) {
                $this->updateJobProgress($jobId, [
                    'processed_rows' => $index + 1,
                    'status' => 'processing',
                    'progress_message' => "Processing row " . ($index + 1) . " of " . count($rows) . "...",
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

        $finalStatus = empty($errors) && $imported === 0 && $updated === 0 ? 'failed' : 'completed';

        // Final progress — adapted from Heratio
        $this->updateJobProgress($jobId, [
            'status' => $finalStatus,
            'processed_rows' => count($rows),
            'error_rows' => count($errors),
            'error_log' => $errors,
            'progress_message' => "Completed: {$imported} imported, {$updated} updated, {$skipped} skipped, " . count($errors) . " errors",
        ]);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Create or update a single entity from mapped/transformed row data.
     * Adapted from Heratio DataMigrationService::importRecord().
     */
    private function createEntity(string $entityType, array $data, array &$legacyIdMap, string $importType = 'create'): int
    {
        $userId = Auth::id();
        $now = now();

        return match ($entityType) {
            'record' => $this->importRecord($data, $legacyIdMap, $userId, $now, $importType),
            'agent' => $this->importAgent($data, $userId, $now, $importType),
            'accession' => $this->importAccession($data, $userId, $now, $importType),
            'repository' => $this->importRepository($data, $userId, $now, $importType),
            default => 0,
        };
    }

    /**
     * Import a single record entity.
     * Adapted from Heratio DataMigrationService::importInformationObject().
     */
    private function importRecord(array $data, array &$legacyIdMap, ?int $userId, $now, string $importType): int
    {
        // Resolve parent from legacy ID — adapted from Heratio
        $parentId = null;
        if (!empty($data['parent_legacy_id']) && isset($legacyIdMap[$data['parent_legacy_id']])) {
            $parentId = $legacyIdMap[$data['parent_legacy_id']];
        }

        // Check for existing record if update mode (PostgreSQL ILIKE)
        $existingId = null;
        if ($importType === 'update' && !empty($data['identifier'])) {
            $existingId = DB::table('records')
                ->whereRaw('identifier ILIKE ?', [$data['identifier']])
                ->value('id');
        }

        if ($existingId && $importType === 'update') {
            $updateData = [];
            $allowedFields = [
                'title', 'alternate_title', 'description', 'scope_and_content',
                'date_start', 'date_end', 'date_expression', 'extent',
                'level_of_description', 'arrangement', 'access_conditions',
                'reproduction_conditions', 'physical_characteristics', 'finding_aids',
                'archival_history', 'acquisition', 'appraisal', 'accruals',
                'location_of_originals', 'location_of_copies', 'related_units',
                'rules', 'sources', 'revision_history', 'description_identifier',
                'source_standard', 'language',
            ];
            foreach ($allowedFields as $f) {
                if (array_key_exists($f, $data) && $data[$f] !== '') {
                    $updateData[$f] = $data[$f];
                }
            }
            if ($parentId !== null) {
                $updateData['parent_id'] = $parentId;
            }
            $updateData['updated_at'] = $now;
            $updateData['updated_by'] = $userId;

            if (!empty($updateData)) {
                DB::table('records')->where('id', $existingId)->update($updateData);
            }
            return $existingId;
        }

        $iri = 'urn:openric:record:' . Str::uuid()->toString();

        $id = (int) DB::table('records')->insertGetId([
            'iri' => $iri,
            'identifier' => $data['identifier'] ?? null,
            'title' => $data['title'] ?? '',
            'alternate_title' => $data['alternate_title'] ?? null,
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
            'revision_history' => $data['revision_history'] ?? null,
            'description_identifier' => $data['description_identifier'] ?? null,
            'source_standard' => $data['source_standard'] ?? null,
            'parent_id' => $parentId,
            'language' => $data['language'] ?? 'en',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    /**
     * Import a single agent entity.
     * Adapted from Heratio DataMigrationService::importActor().
     */
    private function importAgent(array $data, ?int $userId, $now, string $importType): int
    {
        // Check for existing agent if update mode (PostgreSQL ILIKE)
        $existingId = null;
        if ($importType === 'update' && !empty($data['name'])) {
            $existingId = DB::table('agents')
                ->whereRaw('name ILIKE ?', [$data['name']])
                ->value('id');
        }

        if ($existingId && $importType === 'update') {
            $updateData = [];
            $allowedFields = [
                'type', 'dates_of_existence', 'history', 'places', 'legal_status',
                'functions', 'mandates', 'internal_structures', 'general_context',
                'institution_responsible_identifier', 'rules', 'sources', 'revision_history',
                'identifier', 'description_identifier',
            ];
            foreach ($allowedFields as $f) {
                if (array_key_exists($f, $data) && $data[$f] !== '') {
                    $updateData[$f] = $data[$f];
                }
            }
            $updateData['updated_at'] = $now;
            $updateData['updated_by'] = $userId;

            if (!empty($updateData)) {
                DB::table('agents')->where('id', $existingId)->update($updateData);
            }
            return $existingId;
        }

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
            'institution_responsible_identifier' => $data['institution_responsible_identifier'] ?? null,
            'rules' => $data['rules'] ?? null,
            'sources' => $data['sources'] ?? null,
            'revision_history' => $data['revision_history'] ?? null,
            'identifier' => $data['identifier'] ?? null,
            'description_identifier' => $data['description_identifier'] ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Import a single accession entity.
     * Adapted from Heratio DataMigrationService::importAccession().
     */
    private function importAccession(array $data, ?int $userId, $now, string $importType): int
    {
        // Check for existing accession if update mode (PostgreSQL ILIKE)
        $existingId = null;
        if ($importType === 'update' && !empty($data['identifier'])) {
            $existingId = DB::table('accessions')
                ->whereRaw('identifier ILIKE ?', [$data['identifier']])
                ->value('id');
        }

        if ($existingId && $importType === 'update') {
            $updateData = [];
            $allowedFields = [
                'title', 'date', 'source_of_acquisition', 'scope_and_content',
                'archival_history', 'appraisal', 'location_information',
                'physical_characteristics', 'received_extent_units', 'processing_notes',
                'acquisition_type', 'processing_priority', 'processing_status', 'resource_type',
            ];
            foreach ($allowedFields as $f) {
                if (array_key_exists($f, $data) && $data[$f] !== '') {
                    $updateData[$f] = $data[$f];
                }
            }
            $updateData['updated_at'] = $now;
            $updateData['updated_by'] = $userId;

            if (!empty($updateData)) {
                DB::table('accessions')->where('id', $existingId)->update($updateData);
            }
            return $existingId;
        }

        return (int) DB::table('accessions')->insertGetId([
            'identifier' => $data['identifier'] ?? '',
            'title' => $data['title'] ?? null,
            'date' => !empty($data['date']) ? $data['date'] : null,
            'source_of_acquisition' => $data['source_of_acquisition'] ?? null,
            'scope_and_content' => $data['scope_and_content'] ?? null,
            'archival_history' => $data['archival_history'] ?? null,
            'appraisal' => $data['appraisal'] ?? null,
            'location_information' => $data['location_information'] ?? null,
            'physical_characteristics' => $data['physical_characteristics'] ?? null,
            'received_extent_units' => $data['received_extent_units'] ?? null,
            'processing_notes' => $data['processing_notes'] ?? null,
            'acquisition_type' => $data['acquisition_type'] ?? null,
            'processing_priority' => $data['processing_priority'] ?? null,
            'processing_status' => $data['processing_status'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Import a single repository entity.
     * Adapted from Heratio DataMigrationService::importRepository().
     */
    private function importRepository(array $data, ?int $userId, $now, string $importType): int
    {
        // Check for existing repository if update mode (PostgreSQL ILIKE)
        $existingId = null;
        if ($importType === 'update' && !empty($data['name'])) {
            $existingId = DB::table('agents')
                ->where('type', 'repository')
                ->whereRaw('name ILIKE ?', [$data['name']])
                ->value('id');
        }

        if ($existingId && $importType === 'update') {
            $updateData = [];
            $allowedFields = [
                'dates_of_existence', 'history', 'places', 'legal_status', 'functions',
                'mandates', 'internal_structures', 'general_context', 'geocultural_context',
                'collecting_policies', 'buildings', 'holdings', 'finding_aids', 'opening_times',
                'access_conditions', 'disabled_access', 'research_services',
                'reproduction_services', 'public_facilities',
            ];
            foreach ($allowedFields as $f) {
                if (array_key_exists($f, $data) && $data[$f] !== '') {
                    $updateData[$f] = $data[$f];
                }
            }
            $updateData['updated_at'] = $now;
            $updateData['updated_by'] = $userId;

            if (!empty($updateData)) {
                DB::table('agents')->where('id', $existingId)->update($updateData);
            }
            return $existingId;
        }

        $iri = 'urn:openric:agent:' . Str::uuid()->toString();

        return (int) DB::table('agents')->insertGetId([
            'iri' => $iri,
            'name' => $data['name'] ?? '',
            'type' => 'repository',
            'dates_of_existence' => $data['dates_of_existence'] ?? null,
            'history' => $data['history'] ?? null,
            'places' => $data['places'] ?? null,
            'legal_status' => $data['legal_status'] ?? null,
            'functions' => $data['functions'] ?? null,
            'mandates' => $data['mandates'] ?? null,
            'internal_structures' => $data['internal_structures'] ?? null,
            'general_context' => $data['general_context'] ?? null,
            'geocultural_context' => $data['geocultural_context'] ?? null,
            'collecting_policies' => $data['collecting_policies'] ?? null,
            'buildings' => $data['buildings'] ?? null,
            'holdings' => $data['holdings'] ?? null,
            'finding_aids' => $data['finding_aids'] ?? null,
            'opening_times' => $data['opening_times'] ?? null,
            'access_conditions' => $data['access_conditions'] ?? null,
            'disabled_access' => $data['disabled_access'] ?? null,
            'research_services' => $data['research_services'] ?? null,
            'reproduction_services' => $data['reproduction_services'] ?? null,
            'public_facilities' => $data['public_facilities'] ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // =========================================================================
    // Job Progress — adapted from Heratio DataMigrationService::updateJobProgress()
    // =========================================================================

    public function updateJobProgress(int $jobId, array $data): void
    {
        $update = [];
        $allowed = ['status', 'processed_rows', 'error_rows', 'total_rows', 'progress_message'];
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

    /**
     * Cancel a running or pending job.
     * Adapted from Heratio DataMigrationController::cancelJob().
     */
    public function cancelJob(int $jobId): void
    {
        DB::table('data_migration_jobs')
            ->where('id', $jobId)
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'cancelled',
                'progress_message' => 'Job cancelled by user.',
                'updated_at' => now(),
            ]);
    }

    /**
     * Get job results (the created entity IDs and metadata).
     * Adapted from Heratio DataMigrationController::exportCsv() data source.
     */
    public function getJobResults(int $jobId): array
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return [];
        }

        $rollbackData = $job['rollback_data'] ?? [];
        if (empty($rollbackData) || empty($rollbackData['entity_ids'])) {
            return [];
        }

        $entityType = $rollbackData['entity_type'] ?? '';
        $entityIds = $rollbackData['entity_ids'] ?? [];

        $table = match ($entityType) {
            'record' => 'records',
            'agent' => 'agents',
            'accession' => 'accessions',
            default => null,
        };

        if (!$table || empty($entityIds)) {
            return [];
        }

        return DB::table($table)
            ->whereIn('id', $entityIds)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // Presets — adapted from Heratio DataMigrationService save/get/delete mapping
    // =========================================================================

    public function getFieldMappingPresets(): array
    {
        return DB::table('data_migration_presets')
            ->select('id', 'name', 'entity_type', 'category', 'description', 'column_mapping', 'transform_rules', 'is_default', 'created_by', 'created_at', 'updated_at')
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

    public function getPreset(int $id): ?array
    {
        $row = DB::table('data_migration_presets')->where('id', $id)->first();
        if (!$row) {
            return null;
        }
        $arr = (array) $row;
        $arr['column_mapping'] = json_decode((string) ($arr['column_mapping'] ?? '{}'), true);
        $arr['transform_rules'] = json_decode((string) ($arr['transform_rules'] ?? '{}'), true);
        return $arr;
    }

    public function saveFieldMappingPreset(array $data): int
    {
        $columnMapping = $data['column_mapping'] ?? [];
        if (is_array($columnMapping)) {
            $columnMapping = json_encode($columnMapping);
        }
        $transformRules = $data['transform_rules'] ?? [];
        if (is_array($transformRules)) {
            $transformRules = json_encode($transformRules);
        }

        $record = [
            'name' => $data['name'],
            'entity_type' => $data['entity_type'] ?? 'record',
            'category' => $data['category'] ?? 'Custom',
            'description' => $data['description'] ?? null,
            'column_mapping' => $columnMapping,
            'transform_rules' => $transformRules,
            'is_default' => $data['is_default'] ?? 0,
            'created_by' => $data['created_by'] ?? Auth::id(),
            'updated_at' => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('data_migration_presets')->where('id', $data['id'])->update($record);
            return (int) $data['id'];
        }

        $record['created_at'] = now();
        return (int) DB::table('data_migration_presets')->insertGetId($record);
    }

    public function deletePreset(int $id): bool
    {
        return DB::table('data_migration_presets')->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // History — adapted from Heratio patterns
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
    // Rollback — adapted from OpenRiC original
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
                'progress_message' => "Rolled back {$deleted} entities.",
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

    public function getTargetFields(string $entityType): array
    {
        return self::TARGET_FIELDS[$entityType] ?? [];
    }

    // =========================================================================
    // Job CRUD — adapted from Heratio DataMigrationService
    // =========================================================================

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
            'progress_message' => 'Job created, waiting to start.',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

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

    // =========================================================================
    // Stats — adapted from Heratio controller index stats
    // =========================================================================

    public function getStats(): array
    {
        $totalImports = DB::table('data_migration_jobs')->count();
        $successful = DB::table('data_migration_jobs')->where('status', 'completed')->count();
        $failed = DB::table('data_migration_jobs')->where('status', 'failed')->count();
        $totalRecords = (int) DB::table('data_migration_jobs')
            ->where('status', 'completed')
            ->sum('processed_rows');

        return [
            'total_imports' => $totalImports,
            'successful' => $successful,
            'failed' => $failed,
            'total_records' => $totalRecords,
        ];
    }

    // =========================================================================
    // Record Counts — adapted from Heratio batch-export record counts
    // =========================================================================

    public function getRecordCounts(): array
    {
        return [
            'record' => DB::table('records')->count(),
            'agent' => DB::table('agents')->where('type', '!=', 'repository')->count(),
            'repository' => DB::table('agents')->where('type', 'repository')->count(),
            'accession' => DB::table('accessions')->count(),
        ];
    }

    // =========================================================================
    // Batch Export CSV — adapted from Heratio DataMigrationService::batchExportCsv()
    // =========================================================================

    public function batchExportCsv(string $entityType, array $filters = []): StreamedResponse
    {
        $columns = $this->getExportColumns($entityType);

        return new StreamedResponse(function () use ($entityType, $columns, $filters) {
            $handle = fopen('php://output', 'w');

            // Write BOM for Excel compatibility — adapted from Heratio
            fwrite($handle, "\xEF\xBB\xBF");

            // Write header row
            fputcsv($handle, array_values($columns));

            $query = $this->buildExportQuery($entityType, $filters);

            // Stream rows in chunks — adapted from Heratio
            $query->orderByDesc('created_at')
                ->chunk(500, function ($rows) use ($handle, $columns) {
                    foreach ($rows as $row) {
                        $rowData = [];
                        foreach (array_keys($columns) as $col) {
                            $rowData[] = $row->$col ?? '';
                        }
                        fputcsv($handle, $rowData);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $entityType . '_export_' . date('Y-m-d_His') . '.csv"',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Build the export query for a given entity type.
     * Adapted from Heratio DataMigrationService::buildExportQuery().
     */
    private function buildExportQuery(string $entityType, array $filters)
    {
        $query = match ($entityType) {
            'record' => DB::table('records')->select(
                'id', 'iri', 'identifier', 'title', 'scope_and_content', 'archival_history',
                'acquisition', 'extent', 'appraisal', 'accruals', 'arrangement',
                'access_conditions', 'reproduction_conditions', 'physical_characteristics',
                'finding_aids', 'location_of_originals', 'location_of_copies',
                'related_units', 'rules', 'sources', 'revision_history',
                'created_at', 'updated_at'
            ),
            'agent' => DB::table('agents')
                ->where('type', '!=', 'repository')
                ->select(
                    'id', 'iri', 'name', 'type', 'dates_of_existence', 'history',
                    'places', 'legal_status', 'functions', 'mandates',
                    'internal_structures', 'general_context', 'rules', 'sources',
                    'revision_history', 'created_at', 'updated_at'
                ),
            'repository' => DB::table('agents')
                ->where('type', 'repository')
                ->select(
                    'id', 'iri', 'name', 'history', 'geocultural_context',
                    'collecting_policies', 'buildings', 'holdings', 'finding_aids',
                    'opening_times', 'access_conditions', 'created_at', 'updated_at'
                ),
            'accession' => DB::table('accessions')->select(
                'id', 'identifier', 'title', 'date', 'scope_and_content',
                'archival_history', 'appraisal', 'source_of_acquisition',
                'location_information', 'physical_characteristics',
                'processing_notes', 'received_extent_units',
                'created_at', 'updated_at'
            ),
            default => DB::table('records')->whereRaw('1=0'),
        };

        // Apply date filters — adapted from Heratio
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Get column name => header label map for export.
     * Adapted from Heratio DataMigrationService::getExportColumns().
     */
    public function getExportColumns(string $entityType): array
    {
        return match ($entityType) {
            'record' => [
                'id' => 'ID', 'iri' => 'IRI', 'identifier' => 'Identifier', 'title' => 'Title',
                'scope_and_content' => 'Scope and Content', 'archival_history' => 'Archival History',
                'acquisition' => 'Acquisition', 'extent' => 'Extent',
                'appraisal' => 'Appraisal', 'accruals' => 'Accruals', 'arrangement' => 'Arrangement',
                'access_conditions' => 'Access Conditions', 'reproduction_conditions' => 'Reproduction Conditions',
                'physical_characteristics' => 'Physical Characteristics', 'finding_aids' => 'Finding Aids',
                'location_of_originals' => 'Location of Originals', 'location_of_copies' => 'Location of Copies',
                'related_units' => 'Related Units', 'rules' => 'Rules', 'sources' => 'Sources',
                'revision_history' => 'Revision History', 'created_at' => 'Created', 'updated_at' => 'Updated',
            ],
            'agent' => [
                'id' => 'ID', 'iri' => 'IRI', 'name' => 'Name', 'type' => 'Type',
                'dates_of_existence' => 'Dates of Existence',
                'history' => 'History', 'places' => 'Places', 'legal_status' => 'Legal Status',
                'functions' => 'Functions', 'mandates' => 'Mandates',
                'internal_structures' => 'Internal Structures', 'general_context' => 'General Context',
                'rules' => 'Rules', 'sources' => 'Sources', 'revision_history' => 'Revision History',
                'created_at' => 'Created', 'updated_at' => 'Updated',
            ],
            'repository' => [
                'id' => 'ID', 'iri' => 'IRI', 'name' => 'Name', 'history' => 'History',
                'geocultural_context' => 'Geocultural Context', 'collecting_policies' => 'Collecting Policies',
                'buildings' => 'Buildings', 'holdings' => 'Holdings', 'finding_aids' => 'Finding Aids',
                'opening_times' => 'Opening Times', 'access_conditions' => 'Access Conditions',
                'created_at' => 'Created', 'updated_at' => 'Updated',
            ],
            'accession' => [
                'id' => 'ID', 'identifier' => 'Identifier', 'title' => 'Title', 'date' => 'Date',
                'scope_and_content' => 'Scope and Content', 'archival_history' => 'Archival History',
                'appraisal' => 'Appraisal', 'source_of_acquisition' => 'Source of Acquisition',
                'location_information' => 'Location', 'physical_characteristics' => 'Physical Characteristics',
                'processing_notes' => 'Processing Notes', 'received_extent_units' => 'Received Extent',
                'created_at' => 'Created', 'updated_at' => 'Updated',
            ],
            default => ['id' => 'ID'],
        };
    }
}
