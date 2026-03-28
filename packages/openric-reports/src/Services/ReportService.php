<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Reports\Contracts\ReportServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportService — SPARQL-based statistics and PostgreSQL audit reporting.
 *
 * Adapted from Heratio ahg-reports ReportService (483 lines).
 * Queries Fuseki for entity counts by type, and PostgreSQL for user/access stats.
 */
class ReportService implements ReportServiceInterface
{
    protected TriplestoreServiceInterface $triplestore;

    public function __construct(TriplestoreServiceInterface $triplestore)
    {
        $this->triplestore = $triplestore;
    }

    public function getDashboardStats(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        // Count entities by RiC-O type
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
            // Strip prefix for display
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

        return $stats;
    }

    public function getCreationStats(string $period = 'month'): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        // Get entities created in the last year, grouped by creation date
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

        // Aggregate by period
        $grouped = [];
        foreach ($results as $row) {
            $date = $row['created'] ?? '';
            $key = match ($period) {
                'year'  => substr($date, 0, 4),
                'week'  => date('Y-W', strtotime($date) ?: 0),
                default => substr($date, 0, 7), // month
            };
            $grouped[$key] = ($grouped[$key] ?? 0) + (int) ($row['count'] ?? 0);
        }

        return [
            'period'  => $period,
            'data'    => $grouped,
        ];
    }

    public function getAccessStats(): array
    {
        // Embargo statistics from PostgreSQL
        $embargoStats = [
            'active'  => (int) DB::table('embargoes')->where('status', 'active')->count(),
            'lifted'  => (int) DB::table('embargoes')->where('status', 'lifted')->count(),
            'expired' => (int) DB::table('embargoes')->where('status', 'expired')->count(),
        ];

        // Rights statement stats
        $rightsByBasis = DB::table('rights_statements')
            ->selectRaw('rights_basis, COUNT(*) as count')
            ->groupBy('rights_basis')
            ->pluck('count', 'rights_basis')
            ->toArray();

        return [
            'embargoes'        => $embargoStats,
            'rights_by_basis'  => $rightsByBasis,
            'total_rights'     => (int) DB::table('rights_statements')->count(),
            'total_tk_labels'  => (int) DB::table('tk_labels')->count(),
        ];
    }

    public function getUserStats(): array
    {
        $totalUsers = (int) DB::table('users')->count();
        $activeUsers = (int) DB::table('users')->where('is_active', true)->count();

        // Activity from audit log
        $recentActivity = DB::table('audit_log')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();

        $topUsers = DB::table('audit_log')
            ->join('users', 'audit_log.user_id', '=', 'users.id')
            ->where('audit_log.created_at', '>=', now()->subDays(30))
            ->selectRaw('users.name, COUNT(*) as action_count')
            ->groupBy('users.name')
            ->orderByDesc('action_count')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'total_users'     => $totalUsers,
            'active_users'    => $activeUsers,
            'recent_activity' => $recentActivity,
            'top_users'       => $topUsers,
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

        return array_map(fn (array $row) => [
            'iri'     => $row['collection'] ?? '',
            'title'   => $row['title'] ?? '',
            'records' => (int) ($row['count'] ?? 0),
        ], $results);
    }

    public function getSearchStats(): array
    {
        // Search analytics from audit log entries with action 'search'
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
            'total_searches'     => $totalSearches,
            'recent_searches'    => $recentSearches,
            'top_search_terms'   => $topSearchTerms,
        ];
    }

    public function exportReport(string $type, array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // Write header from first row keys
            if (!empty($data)) {
                $first = reset($data);
                if (is_array($first)) {
                    fputcsv($out, array_keys($first));
                } elseif (is_object($first)) {
                    fputcsv($out, array_keys((array) $first));
                }
            }

            // Write rows
            foreach ($data as $row) {
                fputcsv($out, (array) $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
