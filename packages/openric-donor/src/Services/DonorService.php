<?php

declare(strict_types=1);

namespace OpenRiC\Donor\Services;

use OpenRiC\Donor\Contracts\DonorServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Donor service — adapted from Heratio DonorService (302 lines)
 * and DonorBrowseService (55 lines).
 *
 * Heratio stores donors in AtoM's actor/donor/actor_i18n/object tables with
 * slug-based lookups, i18n support, contact_information table for addresses,
 * and relation table for accession links (type_id=167).
 *
 * OpenRiC stores donors in a single PostgreSQL table with soft deletes.
 * Accession links are via FK in the accessions table (donor_id).
 */
class DonorService implements DonorServiceInterface
{
    /**
     * Browse donors with pagination, filtering, and search.
     *
     * Adapted from Heratio DonorBrowseService which queries
     * donor + actor_i18n + object + slug + actor with culture-based joins.
     */
    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $sort = $params['sort'] ?? 'alphabetic';
        $sortDir = strtolower($params['sortDir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $subquery = trim($params['subquery'] ?? '');
        $donorType = trim($params['donorType'] ?? '');
        $isActive = $params['isActive'] ?? null;

        $query = DB::table('donors')
            ->leftJoin('users as creator', 'donors.created_by', '=', 'creator.id')
            ->whereNull('donors.deleted_at');

        // Search filter
        if ($subquery !== '') {
            $query->where(function ($q) use ($subquery) {
                $q->where('donors.name', 'ILIKE', "%{$subquery}%")
                  ->orWhere('donors.contact_person', 'ILIKE', "%{$subquery}%")
                  ->orWhere('donors.institution', 'ILIKE', "%{$subquery}%")
                  ->orWhere('donors.email', 'ILIKE', "%{$subquery}%");
            });
        }

        // Type filter
        if ($donorType !== '') {
            $query->where('donors.donor_type', $donorType);
        }

        // Active filter
        if ($isActive !== null) {
            $query->where('donors.is_active', (bool) $isActive);
        }

        $total = $query->count();

        // Sorting
        switch ($sort) {
            case 'alphabetic':
                $query->orderBy('donors.name', $sortDir);
                break;
            case 'identifier':
                $query->orderBy('donors.uuid', $sortDir);
                break;
            case 'type':
                $query->orderBy('donors.donor_type', $sortDir);
                break;
            case 'lastUpdated':
            default:
                $query->orderBy('donors.updated_at', $sortDir);
                break;
        }

        $rows = $query->select([
                'donors.id',
                'donors.uuid',
                'donors.name',
                'donors.institution',
                'donors.donor_type',
                'donors.email',
                'donors.phone',
                'donors.city',
                'donors.country_code',
                'donors.is_active',
                'donors.updated_at',
                'creator.name as created_by_name',
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
     * Find a donor by ID with full details.
     *
     * Adapted from Heratio DonorService::getById() which joins
     * donor + actor + object + slug + actor_i18n with culture filter.
     */
    public function find(int $id): ?array
    {
        $row = DB::table('donors')
            ->leftJoin('users as creator', 'donors.created_by', '=', 'creator.id')
            ->where('donors.id', $id)
            ->whereNull('donors.deleted_at')
            ->select([
                'donors.*',
                'creator.name as created_by_name',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return (array) $row;
    }

    /**
     * Create a new donor.
     *
     * Adapted from Heratio DonorService::create() which inserts into
     * object, slug, actor, donor, and actor_i18n tables (5 inserts in transaction),
     * plus contact_information + contact_information_i18n for addresses.
     * OpenRiC inserts into a single donors table.
     */
    public function create(array $data, int $userId): int
    {
        return DB::table('donors')->insertGetId([
            'uuid'            => $data['uuid'] ?? (string) Str::uuid(),
            'name'            => $data['name'],
            'contact_person'  => $data['contact_person'] ?? null,
            'institution'     => $data['institution'] ?? null,
            'email'           => $data['email'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'address'         => $data['address'] ?? null,
            'city'            => $data['city'] ?? null,
            'region'          => $data['region'] ?? null,
            'country_code'    => $data['country_code'] ?? null,
            'postal_code'     => $data['postal_code'] ?? null,
            'donor_type'      => $data['donor_type'] ?? 'individual',
            'notes'           => $data['notes'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'created_by'      => $userId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /**
     * Update an existing donor.
     *
     * Adapted from Heratio DonorService::update() which updates
     * actor, actor_i18n (upsert), contact_information sync, and object tables.
     */
    public function update(int $id, array $data): void
    {
        $fields = [
            'name', 'contact_person', 'institution', 'email', 'phone',
            'address', 'city', 'region', 'country_code', 'postal_code',
            'donor_type', 'notes', 'is_active',
        ];

        $update = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated_at'] = now();

        DB::table('donors')->where('id', $id)->whereNull('deleted_at')->update($update);
    }

    /**
     * Soft-delete a donor.
     *
     * Adapted from Heratio DonorService::delete() which hard-deletes from
     * contact_information_i18n, contact_information, relation_i18n, relation,
     * slug, object, note_i18n, note, object_term_relation, actor_i18n,
     * donor, actor, slug, object (14+ deletes in transaction).
     * OpenRiC uses soft delete.
     */
    public function delete(int $id): void
    {
        DB::table('donors')
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'is_active'  => false,
            ]);
    }

    /**
     * Get donor statistics for dashboard.
     *
     * No direct Heratio equivalent — Heratio's donor management lacks
     * a dedicated dashboard. OpenRiC adds analytics.
     */
    public function getDonorStats(): array
    {
        $total = DB::table('donors')->whereNull('deleted_at')->count();

        $active = DB::table('donors')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->count();

        $byType = DB::table('donors')
            ->whereNull('deleted_at')
            ->select('donor_type', DB::raw('COUNT(*) as count'))
            ->groupBy('donor_type')
            ->orderByDesc('count')
            ->pluck('count', 'donor_type')
            ->toArray();

        $recentCount = DB::table('donors')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total'       => $total,
            'active'      => $active,
            'byType'      => $byType,
            'recentCount' => $recentCount,
        ];
    }

    /**
     * Get recently created/updated donors.
     */
    public function getRecentDonors(int $limit = 10): array
    {
        return DB::table('donors')
            ->whereNull('deleted_at')
            ->select([
                'id', 'uuid', 'name', 'institution', 'donor_type',
                'email', 'city', 'country_code', 'is_active', 'created_at', 'updated_at',
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get all donations (accessions) for a donor.
     *
     * Adapted from Heratio DonorService::getRelatedAccessions() which queries
     * relation + accession + accession_i18n + slug via relation table (type_id=167).
     * OpenRiC queries the accessions table directly via donor_id FK.
     */
    public function getDonationsForDonor(int $donorId): array
    {
        return DB::table('accessions')
            ->where('donor_id', $donorId)
            ->select([
                'id', 'accession_number', 'title', 'received_date',
                'processing_status', 'extent', 'created_at',
            ])
            ->orderBy('received_date', 'desc')
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get all accessions linked to a donor (alias for getDonationsForDonor).
     *
     * Adapted from Heratio DonorService::getRelatedAccessions().
     */
    public function getAccessionsForDonor(int $donorId): array
    {
        return $this->getDonationsForDonor($donorId);
    }
}
