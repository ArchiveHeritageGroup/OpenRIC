<?php

declare(strict_types=1);

namespace OpenRiC\Accession\Services;

use OpenRiC\Accession\Contracts\AccessionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Accession service — adapted from Heratio AccessionService (597 lines)
 * and AccessionBrowseService (121 lines).
 *
 * Heratio stores accessions in AtoM's object/accession/accession_i18n tables
 * with slug-based lookups and i18n support. Heratio also uses relation table
 * for donor links (type_id=167) and information_object links (type_id=174).
 *
 * OpenRiC stores accessions as operational data in PostgreSQL (accessions table)
 * with accession_items for line items. Links to archival records use IRIs
 * stored in the object_iri column, referencing Fuseki entities.
 */
class AccessionService implements AccessionServiceInterface
{
    /**
     * Browse accessions with pagination, filtering, and search.
     *
     * Adapted from Heratio AccessionBrowseService::browse() which queries
     * accession + accession_i18n + object + slug with culture-based joins.
     */
    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $sort = $params['sort'] ?? 'lastUpdated';
        $sortDir = strtolower($params['sortDir'] ?? '') === 'asc' ? 'asc' : 'desc';
        $subquery = trim($params['subquery'] ?? '');
        $status = trim($params['status'] ?? '');

        $query = DB::table('accessions')
            ->leftJoin('users as creator', 'accessions.created_by', '=', 'creator.id')
            ->leftJoin('users as processor', 'accessions.processed_by', '=', 'processor.id');

        // Search filter
        if ($subquery !== '') {
            $query->where(function ($q) use ($subquery) {
                $q->where('accessions.title', 'ILIKE', "%{$subquery}%")
                  ->orWhere('accessions.accession_number', 'ILIKE', "%{$subquery}%")
                  ->orWhere('accessions.description', 'ILIKE', "%{$subquery}%");
            });
        }

        // Status filter
        if ($status !== '') {
            $query->where('accessions.processing_status', $status);
        }

        $total = $query->count();

        // Sorting
        switch ($sort) {
            case 'alphabetic':
                $query->orderBy('accessions.title', $sortDir);
                break;
            case 'identifier':
                $query->orderBy('accessions.accession_number', $sortDir);
                break;
            case 'date':
                $query->orderBy('accessions.received_date', $sortDir);
                break;
            case 'lastUpdated':
            default:
                $query->orderBy('accessions.updated_at', $sortDir);
                break;
        }

        $rows = $query->select([
                'accessions.id',
                'accessions.accession_number',
                'accessions.title',
                'accessions.received_date',
                'accessions.processing_status',
                'accessions.extent',
                'accessions.updated_at',
                'creator.name as created_by_name',
                'processor.name as processed_by_name',
            ])
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        return [
            'hits'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Find an accession by ID with full details.
     *
     * Adapted from Heratio AccessionService::getById() which joins
     * accession + object + slug + accession_i18n with culture filter.
     */
    public function find(int $id): ?array
    {
        $row = DB::table('accessions')
            ->leftJoin('users as creator', 'accessions.created_by', '=', 'creator.id')
            ->leftJoin('users as processor', 'accessions.processed_by', '=', 'processor.id')
            ->leftJoin('donors', 'accessions.donor_id', '=', 'donors.id')
            ->where('accessions.id', $id)
            ->select([
                'accessions.*',
                'creator.name as created_by_name',
                'processor.name as processed_by_name',
                'donors.name as donor_name',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        $result = (array) $row;

        // Get accession items
        $result['items'] = DB::table('accession_items')
            ->where('accession_id', $id)
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

        return $result;
    }

    /**
     * Create a new accession.
     *
     * Adapted from Heratio AccessionService::create() which inserts into
     * object, accession, accession_i18n, and slug tables (4 inserts in transaction).
     * OpenRiC inserts into a single accessions table.
     */
    public function create(array $data, int $userId): int
    {
        return DB::transaction(function () use ($data, $userId): int {
            $id = DB::table('accessions')->insertGetId([
                'accession_number'    => $data['accession_number'] ?? $this->generateAccessionNumber(),
                'title'               => $data['title'] ?? null,
                'donor_id'            => !empty($data['donor_id']) ? (int) $data['donor_id'] : null,
                'received_date'       => $data['received_date'] ?? null,
                'description'         => $data['description'] ?? null,
                'extent'              => $data['extent'] ?? null,
                'condition_notes'     => $data['condition_notes'] ?? null,
                'access_restrictions' => $data['access_restrictions'] ?? null,
                'processing_status'   => $data['processing_status'] ?? 'pending',
                'processed_by'        => null,
                'processed_at'        => null,
                'object_iri'          => $data['object_iri'] ?? null,
                'created_by'          => $userId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Create accession items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (empty($item['description']) && empty($item['object_iri'])) {
                        continue;
                    }
                    DB::table('accession_items')->insert([
                        'accession_id' => $id,
                        'object_iri'   => $item['object_iri'] ?? null,
                        'description'  => $item['description'] ?? null,
                        'quantity'     => (int) ($item['quantity'] ?? 1),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            return $id;
        });
    }

    /**
     * Update an existing accession.
     *
     * Adapted from Heratio AccessionService::update() which updates
     * accession, accession_i18n (upsert), and object tables.
     */
    public function update(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $fields = [
                'accession_number', 'title', 'donor_id', 'received_date',
                'description', 'extent', 'condition_notes', 'access_restrictions',
                'processing_status', 'processed_by', 'processed_at', 'object_iri',
            ];

            $update = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $update[$field] = $data[$field];
                }
            }
            $update['updated_at'] = now();

            DB::table('accessions')->where('id', $id)->update($update);

            // Sync accession items if provided
            if (array_key_exists('items', $data) && is_array($data['items'])) {
                // Get existing item IDs
                $existingIds = DB::table('accession_items')
                    ->where('accession_id', $id)
                    ->pluck('id')
                    ->toArray();

                $keepIds = [];
                foreach ($data['items'] as $item) {
                    if (empty($item['description']) && empty($item['object_iri'])) {
                        continue;
                    }

                    if (!empty($item['id'])) {
                        // Update existing item
                        DB::table('accession_items')
                            ->where('id', $item['id'])
                            ->where('accession_id', $id)
                            ->update([
                                'object_iri'  => $item['object_iri'] ?? null,
                                'description' => $item['description'] ?? null,
                                'quantity'    => (int) ($item['quantity'] ?? 1),
                                'updated_at'  => now(),
                            ]);
                        $keepIds[] = (int) $item['id'];
                    } else {
                        // Insert new item
                        $newId = DB::table('accession_items')->insertGetId([
                            'accession_id' => $id,
                            'object_iri'   => $item['object_iri'] ?? null,
                            'description'  => $item['description'] ?? null,
                            'quantity'     => (int) ($item['quantity'] ?? 1),
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                        $keepIds[] = $newId;
                    }
                }

                // Delete items not in the submitted set
                $deleteIds = array_diff($existingIds, $keepIds);
                if (!empty($deleteIds)) {
                    DB::table('accession_items')->whereIn('id', $deleteIds)->delete();
                }
            }
        });
    }

    /**
     * Delete an accession and its items.
     *
     * Adapted from Heratio AccessionService::delete() which deletes from
     * deaccession_i18n, deaccession, relation_i18n, relation, slug, object,
     * accession_i18n, accession (8+ deletes in transaction).
     * OpenRiC deletes from accession_items and accessions.
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            DB::table('accession_items')->where('accession_id', $id)->delete();
            DB::table('accessions')->where('id', $id)->delete();
        });
    }

    /**
     * Get recent accessions for dashboard.
     *
     * Adapted from Heratio AccessionController::dashboard() which queries
     * accession + object for recently created records.
     */
    public function getRecentAccessions(int $limit = 10): array
    {
        return DB::table('accessions')
            ->leftJoin('users as creator', 'accessions.created_by', '=', 'creator.id')
            ->select([
                'accessions.id',
                'accessions.accession_number',
                'accessions.title',
                'accessions.received_date',
                'accessions.processing_status',
                'accessions.created_at',
                'creator.name as created_by_name',
            ])
            ->orderBy('accessions.created_at', 'desc')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get accession statistics for dashboard.
     *
     * Adapted from Heratio AccessionController::dashboard() which computes
     * total, byStatus, byPriority, and recentCount from accession + term tables.
     */
    public function getAccessionStats(): array
    {
        $total = DB::table('accessions')->count();

        $byStatus = DB::table('accessions')
            ->select('processing_status', DB::raw('COUNT(*) as count'))
            ->groupBy('processing_status')
            ->orderByDesc('count')
            ->pluck('count', 'processing_status')
            ->toArray();

        $recentCount = DB::table('accessions')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $totalItems = DB::table('accession_items')->count();

        return [
            'total'       => $total,
            'byStatus'    => $byStatus,
            'recentCount' => $recentCount,
            'totalItems'  => $totalItems,
        ];
    }

    /**
     * Link an accession to a record IRI.
     *
     * Adapted from Heratio's relation table approach (type_id=174 for
     * ACCESSION link). OpenRiC stores the IRI directly in object_iri column
     * and also stores per-item links in accession_items.
     */
    public function linkToRecord(int $accessionId, string $recordIri): void
    {
        DB::table('accessions')
            ->where('id', $accessionId)
            ->update([
                'object_iri'  => $recordIri,
                'updated_at'  => now(),
            ]);
    }

    /**
     * Get all record IRIs linked to an accession.
     *
     * Adapted from Heratio AccessionService::getInformationObjects() which
     * queries the relation table with type_id=174.
     */
    public function getLinkedRecords(int $accessionId): array
    {
        $records = [];

        // Main accession link
        $accession = DB::table('accessions')
            ->where('id', $accessionId)
            ->select('object_iri')
            ->first();

        if ($accession !== null && !empty($accession->object_iri)) {
            $records[] = [
                'iri'         => $accession->object_iri,
                'description' => 'Primary linked record',
            ];
        }

        // Item-level links
        $items = DB::table('accession_items')
            ->where('accession_id', $accessionId)
            ->whereNotNull('object_iri')
            ->where('object_iri', '!=', '')
            ->select('object_iri', 'description')
            ->get();

        foreach ($items as $item) {
            $records[] = [
                'iri'         => $item->object_iri,
                'description' => $item->description ?? 'Accession item',
            ];
        }

        return $records;
    }

    /**
     * Generate the next accession number.
     *
     * Adapted from Heratio's AccessionController::numbering() pattern.
     * Format: YYYY-NNN (e.g., 2026-001, 2026-002).
     */
    public function generateAccessionNumber(string $prefix = ''): string
    {
        if ($prefix === '') {
            $prefix = date('Y');
        }

        $lastNumber = DB::table('accessions')
            ->where('accession_number', 'LIKE', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING(accession_number FROM POSITION('-' IN accession_number) + 1) AS INTEGER) DESC")
            ->value('accession_number');

        if ($lastNumber !== null) {
            $parts = explode('-', $lastNumber);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
