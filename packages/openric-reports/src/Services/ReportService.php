<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\Reports\Contracts\ReportServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportService -- SPARQL + PostgreSQL reporting.
 *
 * Adapted from Heratio ahg-reports ReportService (483 LOC).
 * Uses PostgreSQL-specific syntax (ILIKE, pg_size_pretty, jsonb operators).
 * Queries Fuseki for RiC-O entity counts by type, and PostgreSQL for audit/entity stats.
 */
class ReportService implements ReportServiceInterface
{
    protected TriplestoreServiceInterface $triplestore;

    public function __construct(TriplestoreServiceInterface $triplestore)
    {
        $this->triplestore = $triplestore;
    }

    // =====================================================================
    //  Dashboard
    // =====================================================================

    public function getDashboardStats(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        // Count entities by RiC-O type via SPARQL
        $sparql = $prefixes . '
            SELECT ?type (COUNT(?s) AS ?count) WHERE {
                ?s a ?type .
                VALUES ?type {
                    rico:Record rico:RecordSet rico:RecordPart
                    rico:Person rico:Family rico:CorporateBody
                    rico:Place rico:Activity rico:Instantiation
                }
            }
            GROUP BY ?type
            ORDER BY DESC(?count)
            LIMIT 20
        ';

        $results = $this->triplestore->select($sparql);

        $stats = [];
        $total = 0;
        foreach ($results as $row) {
            $type = $row['type'] ?? 'unknown';
            $shortType = str_contains($type, '#') ? substr($type, (int) strrpos($type, '#') + 1) : $type;
            $shortType = str_contains($shortType, '/') ? substr($shortType, (int) strrpos($shortType, '/') + 1) : $shortType;
            $count = (int) ($row['count'] ?? 0);
            $stats[$shortType] = $count;
            $total += $count;
        }

        $stats['total_entities'] = $total;
        $stats['total_triples'] = $this->triplestore->countTriples();

        // PostgreSQL stats
        $stats['total_users'] = (int) DB::table('users')->count();
        $stats['recent_logins'] = (int) DB::table('sessions')
            ->where('last_activity', '>=', now()->subDays(7)->timestamp)
            ->count();

        // Record counts from PostgreSQL
        $stats['descriptions'] = $this->safeCount('record_descriptions');
        $stats['agents'] = $this->safeCount('agents');
        $stats['repositories'] = $this->safeCount('repositories');
        $stats['accessions'] = $this->safeCount('accessions');
        $stats['digital_objects'] = $this->safeCount('digital_objects');
        $stats['donors'] = $this->safeCount('donors');
        $stats['physical_storage'] = $this->safeCount('physical_storage_locations');

        // Publication status counts
        $stats['published'] = (int) DB::table('record_descriptions')
            ->where('publication_status', 'published')
            ->count();
        $stats['draft'] = (int) DB::table('record_descriptions')
            ->where('publication_status', 'draft')
            ->count();

        // Recent updates (7 days)
        $stats['recent_updates'] = (int) DB::table('audit_log')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Embargo stats
        $stats['active_embargoes'] = $this->safeCount('embargoes', ['status' => 'active']);
        $stats['total_rights'] = $this->safeCount('rights_statements');

        // Database size (PostgreSQL-specific)
        try {
            $dbName = DB::connection()->getDatabaseName();
            $sizeResult = DB::selectOne("SELECT pg_size_pretty(pg_database_size(?)) AS size", [$dbName]);
            $stats['database_size'] = $sizeResult->size ?? 'N/A';
        } catch (\Exception $e) {
            $stats['database_size'] = 'N/A';
        }

        return $stats;
    }

    // =====================================================================
    //  Entity Reports
    // =====================================================================

    public function reportDescriptions(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subMonth()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $levelId = $params['level'] ?? null;
        $pubStatus = $params['publicationStatus'] ?? null;
        $repositoryId = $params['repositoryId'] ?? null;
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('record_descriptions as rd')
            ->leftJoin('record_description_i18n as rdi', function ($join) use ($culture) {
                $join->on('rd.id', '=', 'rdi.record_description_id')
                    ->where('rdi.culture', '=', $culture);
            })
            ->leftJoin('terms as level_term', 'rd.level_of_description_id', '=', 'level_term.id')
            ->leftJoin('term_i18n as level_name', function ($join) use ($culture) {
                $join->on('level_term.id', '=', 'level_name.term_id')
                    ->where('level_name.culture', '=', $culture);
            })
            ->select(
                'rd.id',
                'rd.identifier',
                'rd.level_of_description_id',
                'rd.publication_status',
                'rd.repository_id',
                'rdi.title',
                'rdi.scope_and_content',
                'level_name.name as level_name',
                'rd.created_at',
                'rd.updated_at'
            );

        $dateCol = $dateOf === 'updated_at' ? 'rd.updated_at' : 'rd.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }
        if ($levelId) {
            $query->where('rd.level_of_description_id', $levelId);
        }
        if ($pubStatus) {
            $query->where('rd.publication_status', $pubStatus);
        }
        if ($repositoryId) {
            $query->where('rd.repository_id', $repositoryId);
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportAgents(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subYear()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $entityType = $params['entityType'] ?? null;
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('agents as a')
            ->leftJoin('agent_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.agent_id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('terms as type_term', 'a.entity_type_id', '=', 'type_term.id')
            ->leftJoin('term_i18n as type_name', function ($join) use ($culture) {
                $join->on('type_term.id', '=', 'type_name.term_id')
                    ->where('type_name.culture', '=', $culture);
            })
            ->select(
                'a.id',
                'a.entity_type_id',
                'a.description_identifier',
                'ai.authorized_form_of_name',
                'ai.dates_of_existence',
                'ai.history',
                'type_name.name as entity_type_name',
                'a.created_at',
                'a.updated_at'
            );

        $dateCol = $dateOf === 'updated_at' ? 'a.updated_at' : 'a.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }
        if ($entityType) {
            $query->where('a.entity_type_id', $entityType);
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportRepositories(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subMonth()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('repositories as r')
            ->leftJoin('repository_i18n as ri', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ri.repository_id')
                    ->where('ri.culture', '=', $culture);
            })
            ->select(
                'r.id',
                'r.identifier',
                'ri.authorized_form_of_name',
                'ri.geocultural_context',
                'ri.collecting_policies',
                'r.created_at',
                'r.updated_at',
                DB::raw('(SELECT COUNT(*) FROM record_descriptions WHERE repository_id = r.id) AS holdings_count')
            );

        $dateCol = $dateOf === 'updated_at' ? 'r.updated_at' : 'r.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportAccessions(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subYear()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('accessions as acc')
            ->leftJoin('accession_i18n as acci', function ($join) use ($culture) {
                $join->on('acc.id', '=', 'acci.accession_id')
                    ->where('acci.culture', '=', $culture);
            })
            ->select(
                'acc.id',
                'acc.identifier',
                'acci.title',
                'acci.scope_and_content',
                'acci.appraisal',
                'acci.processing_notes',
                'acc.created_at',
                'acc.updated_at'
            );

        $dateCol = $dateOf === 'updated_at' ? 'acc.updated_at' : 'acc.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportDonors(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subYear()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('donors as d')
            ->leftJoin('donor_i18n as di', function ($join) use ($culture) {
                $join->on('d.id', '=', 'di.donor_id')
                    ->where('di.culture', '=', $culture);
            })
            ->leftJoin('contact_information as ci', 'd.id', '=', 'ci.contactable_id')
            ->select(
                'd.id',
                'di.authorized_form_of_name',
                'ci.email',
                'ci.telephone',
                'ci.city',
                'ci.region',
                'd.created_at',
                'd.updated_at'
            );

        $dateCol = $dateOf === 'updated_at' ? 'd.updated_at' : 'd.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportPhysicalStorage(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? now()->subYear()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $dateOf = $params['dateOf'] ?? 'created_at';
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('physical_storage_locations as ps')
            ->leftJoin('physical_storage_i18n as psi', function ($join) use ($culture) {
                $join->on('ps.id', '=', 'psi.physical_storage_location_id')
                    ->where('psi.culture', '=', $culture);
            })
            ->leftJoin('terms as type_term', 'ps.type_id', '=', 'type_term.id')
            ->leftJoin('term_i18n as type_name', function ($join) use ($culture) {
                $join->on('type_term.id', '=', 'type_name.term_id')
                    ->where('type_name.culture', '=', $culture);
            })
            ->select(
                'ps.id',
                'ps.type_id',
                'psi.name',
                'psi.location',
                'psi.description',
                'type_name.name as type_name',
                'ps.created_at',
                'ps.updated_at'
            );

        $dateCol = $dateOf === 'updated_at' ? 'ps.updated_at' : 'ps.created_at';
        if ($dateStart) {
            $query->where($dateCol, '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($dateCol, '<=', $dateEnd . ' 23:59:59');
        }

        $total = $query->count();
        $results = $query->orderByDesc($dateCol)
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

    public function reportTaxonomies(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $dateStart = $params['dateStart'] ?? null;
        $dateEnd = $params['dateEnd'] ?? null;
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);
        $sort = $params['sort'] ?? 'nameUp';

        $query = DB::table('taxonomies as t')
            ->leftJoin('taxonomy_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.taxonomy_id')
                    ->where('ti.culture', '=', $culture);
            })
            ->select(
                't.id',
                't.usage',
                'ti.name',
                'ti.note',
                't.created_at',
                't.updated_at',
                DB::raw('(SELECT COUNT(*) FROM terms WHERE terms.taxonomy_id = t.id) AS term_count')
            );

        if ($dateStart) {
            $query->where('t.created_at', '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where('t.updated_at', '<=', $dateEnd . ' 23:59:59');
        }

        $orderMap = [
            'nameUp'      => ['ti.name', 'asc'],
            'nameDown'    => ['ti.name', 'desc'],
            'updatedUp'   => ['t.updated_at', 'asc'],
            'updatedDown' => ['t.updated_at', 'desc'],
        ];
        $order = $orderMap[$sort] ?? ['ti.name', 'asc'];

        $total = $query->count();
        $results = $query->orderBy($order[0], $order[1])
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

    public function reportUpdates(array $params): array
    {
        $dateStart = $params['dateStart'] ?? now()->subMonth()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $entityType = $params['entityType'] ?? null;
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $query = DB::table('audit_log')
            ->where('created_at', '>=', $dateStart . ' 00:00:00')
            ->where('created_at', '<=', $dateEnd . ' 23:59:59');

        $entityTypeMap = [
            'RecordDescription' => 'record_descriptions',
            'Agent'             => 'agents',
            'Repository'        => 'repositories',
            'Accession'         => 'accessions',
            'PhysicalStorage'   => 'physical_storage_locations',
            'Donor'             => 'donors',
        ];

        if ($entityType && isset($entityTypeMap[$entityType])) {
            $query->where('entity_type', $entityTypeMap[$entityType]);
        } elseif (!$entityType) {
            $query->whereIn('entity_type', array_values($entityTypeMap));
        }

        $total = $query->count();
        $results = $query->select('id', 'entity_type', 'entity_id', 'action', 'created_at', 'updated_at')
            ->orderByDesc('created_at')
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

    public function reportUserActivity(array $params): array
    {
        $dateStart = $params['dateStart'] ?? now()->subMonth()->format('Y-m-d');
        $dateEnd = $params['dateEnd'] ?? now()->format('Y-m-d');
        $actionUser = $params['actionUser'] ?? null;
        $userAction = $params['userAction'] ?? null;
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        $auditTable = Schema::hasTable('audit_log') ? 'audit_log' : null;
        if (!$auditTable) {
            return [
                'results'    => collect(),
                'total'      => 0,
                'page'       => 1,
                'lastPage'   => 1,
                'limit'      => $limit,
                'auditTable' => null,
            ];
        }

        $query = DB::table($auditTable)
            ->leftJoin('users', $auditTable . '.user_id', '=', 'users.id')
            ->select(
                $auditTable . '.id',
                'users.name as username',
                $auditTable . '.action',
                $auditTable . '.entity_type',
                $auditTable . '.entity_id',
                DB::raw("COALESCE({$auditTable}.new_values->>'title', '') as entity_title"),
                $auditTable . '.created_at'
            );

        if ($dateStart) {
            $query->where($auditTable . '.created_at', '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd) {
            $query->where($auditTable . '.created_at', '<=', $dateEnd . ' 23:59:59');
        }
        if ($actionUser) {
            $query->where('users.name', 'ILIKE', $actionUser);
        }
        if ($userAction) {
            $query->where($auditTable . '.action', $userAction);
        }

        $total = $query->count();
        $results = $query->orderByDesc($auditTable . '.created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'    => $results,
            'total'      => $total,
            'page'       => $page,
            'lastPage'   => max(1, (int) ceil($total / $limit)),
            'limit'      => $limit,
            'auditTable' => $auditTable,
        ];
    }

    public function reportAccess(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $page = max((int) ($params['page'] ?? 1), 1);

        // Embargo statistics
        $embargoStats = [
            'active'  => $this->safeCount('embargoes', ['status' => 'active']),
            'lifted'  => $this->safeCount('embargoes', ['status' => 'lifted']),
            'expired' => $this->safeCount('embargoes', ['status' => 'expired']),
        ];

        // Rights statement stats by basis
        $rightsByBasis = [];
        try {
            $rightsByBasis = DB::table('rights_statements')
                ->selectRaw('rights_basis, COUNT(*) as count')
                ->groupBy('rights_basis')
                ->pluck('count', 'rights_basis')
                ->toArray();
        } catch (\Exception $e) {
            // table may not exist
        }

        // Records with access restrictions
        $restrictedRecords = collect();
        try {
            $query = DB::table('record_descriptions as rd')
                ->leftJoin('record_description_i18n as rdi', function ($join) use ($culture) {
                    $join->on('rd.id', '=', 'rdi.record_description_id')
                        ->where('rdi.culture', '=', $culture);
                })
                ->leftJoin('rights_statements as rs', function ($join) {
                    $join->on('rd.id', '=', 'rs.record_description_id');
                })
                ->leftJoin('embargoes as e', function ($join) {
                    $join->on('rd.id', '=', 'e.record_description_id')
                        ->where('e.status', '=', 'active');
                })
                ->where(function ($q) {
                    $q->whereNotNull('rs.id')
                      ->orWhereNotNull('e.id');
                })
                ->select(
                    'rd.id',
                    'rd.identifier',
                    'rdi.title',
                    'rd.publication_status',
                    'rs.rights_basis',
                    'rs.act_type as restriction_type',
                    'e.expiry_date as embargo_expiry',
                    'rd.created_at'
                );

            $restrictedRecords = $query->orderByDesc('rd.created_at')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            // tables may not exist
        }

        return [
            'embargoes'          => $embargoStats,
            'rights_by_basis'    => $rightsByBasis,
            'total_rights'       => $this->safeCount('rights_statements'),
            'total_tk_labels'    => $this->safeCount('tk_labels'),
            'restricted_records' => $restrictedRecords,
            'page'               => $page,
            'limit'              => $limit,
        ];
    }

    public function reportSpatial(array $params): array
    {
        $culture = $params['culture'] ?? 'en';
        $placeIds = $params['placeIds'] ?? [];
        $level = $params['level'] ?? null;
        $subjects = $params['subjects'] ?? '';
        $topLevelOnly = (bool) ($params['topLevelOnly'] ?? false);
        $requireCoordinates = (bool) ($params['requireCoordinates'] ?? true);

        $query = DB::table('record_descriptions as rd')
            ->leftJoin('record_description_i18n as rdi', function ($join) use ($culture) {
                $join->on('rd.id', '=', 'rdi.record_description_id')
                    ->where('rdi.culture', '=', $culture);
            })
            ->leftJoin('terms as lod', 'rd.level_of_description_id', '=', 'lod.id')
            ->leftJoin('term_i18n as lod_name', function ($join) use ($culture) {
                $join->on('lod.id', '=', 'lod_name.term_id')
                    ->where('lod_name.culture', '=', $culture);
            })
            ->leftJoin('repositories as repo', 'rd.repository_id', '=', 'repo.id')
            ->leftJoin('repository_i18n as repo_name', function ($join) use ($culture) {
                $join->on('repo.id', '=', 'repo_name.repository_id')
                    ->where('repo_name.culture', '=', $culture);
            })
            ->select([
                'rd.id',
                'rd.identifier',
                'rdi.title',
                'rd.latitude',
                'rd.longitude',
                'lod_name.name as level_of_description',
                'repo_name.authorized_form_of_name as repository',
            ]);

        if ($requireCoordinates) {
            $query->whereNotNull('rd.latitude')->whereNotNull('rd.longitude');
        }

        if ($topLevelOnly) {
            $query->whereNull('rd.parent_id');
        }

        if ($level) {
            $query->where('rd.level_of_description_id', $level);
        }

        if (!empty($placeIds)) {
            $ids = is_array($placeIds) ? $placeIds : [$placeIds];
            $query->whereExists(function ($sub) use ($ids) {
                $sub->select(DB::raw(1))
                    ->from('record_description_term as rdt')
                    ->join('terms as t', 'rdt.term_id', '=', 't.id')
                    ->whereColumn('rdt.record_description_id', 'rd.id')
                    ->where('t.taxonomy_id', function ($q) {
                        $q->select('id')->from('taxonomies')->where('usage', 'places')->limit(1);
                    })
                    ->whereIn('rdt.term_id', $ids);
            });
        }

        if ($subjects) {
            $subjectList = array_filter(array_map('trim', explode(',', $subjects)));
            if (!empty($subjectList)) {
                $query->whereExists(function ($sub) use ($subjectList, $culture) {
                    $sub->select(DB::raw(1))
                        ->from('record_description_term as rdt')
                        ->join('term_i18n as sti', function ($join) use ($culture) {
                            $join->on('rdt.term_id', '=', 'sti.term_id')
                                ->where('sti.culture', '=', $culture);
                        })
                        ->whereColumn('rdt.record_description_id', 'rd.id')
                        ->where(function ($q) use ($subjectList) {
                            foreach ($subjectList as $term) {
                                $q->orWhere('sti.name', 'ILIKE', '%' . $term . '%');
                            }
                        });
                });
            }
        }

        $total = (clone $query)->count();
        $results = $query->limit(10)->get();

        return [
            'results' => $results,
            'total'   => $total,
        ];
    }

    // =====================================================================
    //  SPARQL Statistics
    // =====================================================================

    public function getCreationStats(string $period = 'month'): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?created (COUNT(?s) AS ?count) WHERE {
                ?s a ?type .
                ?s rico:dateCreated ?created .
                VALUES ?type {
                    rico:Record rico:RecordSet rico:Person rico:CorporateBody
                }
            }
            GROUP BY ?created
            ORDER BY ?created
            LIMIT 500
        ';

        $results = $this->triplestore->select($sparql);

        $grouped = [];
        foreach ($results as $row) {
            $date = $row['created'] ?? '';
            $key = match ($period) {
                'year'  => substr($date, 0, 4),
                'week'  => date('Y-W', strtotime($date) ?: 0),
                default => substr($date, 0, 7),
            };
            $grouped[$key] = ($grouped[$key] ?? 0) + (int) ($row['count'] ?? 0);
        }

        return [
            'period' => $period,
            'data'   => $grouped,
        ];
    }

    public function getCollectionStats(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?collection ?title (COUNT(?record) AS ?count) WHERE {
                ?collection a rico:RecordSet .
                ?collection rico:title ?title .
                OPTIONAL {
                    ?record rico:isOrWasIncludedIn ?collection .
                }
            }
            GROUP BY ?collection ?title
            ORDER BY DESC(?count)
            LIMIT 50
        ';

        $results = $this->triplestore->select($sparql);

        return array_map(fn(array $row) => [
            'iri'     => $row['collection'] ?? '',
            'title'   => $row['title'] ?? '',
            'records' => (int) ($row['count'] ?? 0),
        ], $results);
    }

    public function getSearchStats(): array
    {
        $totalSearches = (int) DB::table('audit_log')
            ->where('action', 'search')
            ->count();

        $recentSearches = (int) DB::table('audit_log')
            ->where('action', 'search')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $topSearchTerms = DB::table('audit_log')
            ->where('action', 'search')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("new_values->>'query' as search_term, COUNT(*) as count")
            ->groupByRaw("new_values->>'query'")
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();

        return [
            'total_searches'   => $totalSearches,
            'recent_searches'  => $recentSearches,
            'top_search_terms' => $topSearchTerms,
        ];
    }

    // =====================================================================
    //  Lookup Helpers
    // =====================================================================

    public function getLevelsOfDescription(string $culture = 'en'): Collection
    {
        return DB::table('terms as t')
            ->join('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.term_id')
                    ->where('ti.culture', '=', $culture);
            })
            ->join('taxonomies as tx', 't.taxonomy_id', '=', 'tx.id')
            ->where('tx.usage', 'levels_of_description')
            ->select('t.id', 'ti.name')
            ->orderBy('ti.name')
            ->get();
    }

    public function getEntityTypes(string $culture = 'en'): Collection
    {
        return DB::table('terms as t')
            ->join('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.term_id')
                    ->where('ti.culture', '=', $culture);
            })
            ->join('taxonomies as tx', 't.taxonomy_id', '=', 'tx.id')
            ->where('tx.usage', 'entity_types')
            ->select('t.id', 'ti.name')
            ->orderBy('ti.name')
            ->get();
    }

    public function getAvailableCultures(): array
    {
        try {
            return DB::table('settings')
                ->whereNotNull('culture')
                ->select('culture')
                ->distinct()
                ->pluck('culture')
                ->toArray();
        } catch (\Exception $e) {
            return ['en'];
        }
    }

    public function getAuditUsers(): array
    {
        try {
            return DB::table('audit_log')
                ->join('users', 'audit_log.user_id', '=', 'users.id')
                ->select('users.name')
                ->distinct()
                ->whereNotNull('users.name')
                ->orderBy('users.name')
                ->pluck('users.name')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPlaceTerms(string $culture = 'en'): Collection
    {
        return DB::table('terms as t')
            ->join('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.term_id')
                    ->where('ti.culture', '=', $culture);
            })
            ->join('taxonomies as tx', 't.taxonomy_id', '=', 'tx.id')
            ->where('tx.usage', 'places')
            ->orderBy('ti.name')
            ->select('t.id', 'ti.name')
            ->get();
    }

    public function getRepositoryList(string $culture = 'en'): Collection
    {
        return DB::table('repositories as r')
            ->leftJoin('repository_i18n as ri', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ri.repository_id')
                    ->where('ri.culture', '=', $culture);
            })
            ->select('r.id', 'ri.authorized_form_of_name as name')
            ->orderBy('ri.authorized_form_of_name')
            ->get();
    }

    // =====================================================================
    //  Export
    // =====================================================================

    public function exportCsv(array $data, array $headers, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data, $headers): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, $headers);
            foreach ($data as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportGeoJson(Collection $data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $data->map(function ($row) {
                return [
                    'type'       => 'Feature',
                    'properties' => [
                        'id'         => $row->id,
                        'identifier' => $row->identifier,
                        'title'      => $row->title,
                        'level'      => $row->level_of_description ?? null,
                        'repository' => $row->repository ?? null,
                    ],
                    'geometry'   => [
                        'type'        => 'Point',
                        'coordinates' => [(float) ($row->longitude ?? 0), (float) ($row->latitude ?? 0)],
                    ],
                ];
            })->values(),
        ]);
    }

    // =====================================================================
    //  Internal Helpers
    // =====================================================================

    /**
     * Safe count that returns 0 if the table does not exist.
     */
    private function safeCount(string $table, array $where = []): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }
            $query = DB::table($table);
            foreach ($where as $col => $val) {
                $query->where($col, $val);
            }
            return (int) $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
