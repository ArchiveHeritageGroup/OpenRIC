<?php

declare(strict_types=1);

namespace OpenRiC\Statistics\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\Statistics\Contracts\StatisticsServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * StatisticsService — usage tracking, aggregation, geographic, bot filtering, CSV export.
 *
 * Adapted from Heratio ahg-statistics StatisticsService (187 lines).
 * PostgreSQL-native: ILIKE for bot matching, TO_CHAR for date grouping, IRI-based entity refs.
 */
class StatisticsService implements StatisticsServiceInterface
{
    // ──────────────────────────────────────────────
    // Event recording
    // ──────────────────────────────────────────────

    public function recordEvent(array $data): int
    {
        $userAgent = $data['user_agent'] ?? '';
        $ipAddress = $data['ip_address'] ?? '';

        // Skip bots if filtering enabled
        if ($this->getConfig('bot_filtering_enabled', true) && $userAgent !== '' && $this->isBot($userAgent)) {
            return 0;
        }

        // Skip admin views if configured
        if ($this->getConfig('exclude_admin_views', true) && isset($data['user_id'])) {
            $isAdmin = DB::table('users')
                ->where('id', $data['user_id'])
                ->where('is_admin', true)
                ->exists();
            if ($isAdmin) {
                return 0;
            }
        }

        // Anonymize IP if configured
        if ($this->getConfig('anonymize_ip', true) && $ipAddress !== '') {
            $ipAddress = $this->anonymizeIp($ipAddress);
        }

        // Resolve country/city via GeoIP if enabled
        $country = null;
        $city = null;
        if ($this->getConfig('geoip_enabled', true) && $ipAddress !== '') {
            $geo = $this->resolveGeoIp($data['ip_address'] ?? '');
            $country = $geo['country'];
            $city = $geo['city'];
        }

        return DB::table('usage_events')->insertGetId([
            'entity_iri'  => $data['entity_iri'],
            'entity_type' => $data['entity_type'] ?? 'Record',
            'event_type'  => $data['event_type'],
            'user_id'     => $data['user_id'] ?? null,
            'ip_address'  => $ipAddress,
            'user_agent'  => $userAgent !== '' ? mb_substr($userAgent, 0, 512) : null,
            'country'     => $country,
            'city'        => $city,
            'created_at'  => now(),
        ]);
    }

    // ──────────────────────────────────────────────
    // Dashboard stats
    // ──────────────────────────────────────────────

    public function getDashboardStats(string $startDate, string $endDate): array
    {
        $range = [$startDate, $endDate . ' 23:59:59'];

        $views = (int) DB::table('usage_events')
            ->where('event_type', 'view')
            ->whereBetween('created_at', $range)
            ->count();

        $downloads = (int) DB::table('usage_events')
            ->where('event_type', 'download')
            ->whereBetween('created_at', $range)
            ->count();

        $searches = (int) DB::table('usage_events')
            ->where('event_type', 'search')
            ->whereBetween('created_at', $range)
            ->count();

        $uniqueVisitors = (int) DB::table('usage_events')
            ->whereBetween('created_at', $range)
            ->distinct('ip_address')
            ->count('ip_address');

        $uniqueEntities = (int) DB::table('usage_events')
            ->whereBetween('created_at', $range)
            ->distinct('entity_iri')
            ->count('entity_iri');

        return [
            'views'           => $views,
            'downloads'       => $downloads,
            'searches'        => $searches,
            'unique_visitors' => $uniqueVisitors,
            'unique_entities' => $uniqueEntities,
        ];
    }

    // ──────────────────────────────────────────────
    // Top items
    // ──────────────────────────────────────────────

    public function getTopItems(string $eventType, int $limit, string $startDate, string $endDate): Collection
    {
        return DB::table('usage_events')
            ->where('event_type', $eventType)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('entity_iri')
            ->select(
                'entity_iri',
                'entity_type',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('entity_iri', 'entity_type')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────
    // Geographic
    // ──────────────────────────────────────────────

    public function getGeographicStats(string $startDate, string $endDate): array
    {
        $rows = DB::table('usage_events')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->select(
                'country',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors'),
                DB::raw('COUNT(DISTINCT entity_iri) as unique_entities')
            )
            ->groupBy('country')
            ->orderByDesc('event_count')
            ->get();

        return $rows->toArray();
    }

    /**
     * Get geographic stats at the city level, optionally filtered by country.
     *
     * @return array<int, object>
     */
    public function getCityStats(string $startDate, string $endDate, ?string $country = null): array
    {
        $query = DB::table('usage_events')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('city')
            ->where('city', '!=', '');

        if ($country !== null) {
            $query->where('country', $country);
        }

        return $query
            ->select(
                'country',
                'city',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('country', 'city')
            ->orderByDesc('event_count')
            ->limit(100)
            ->get()
            ->toArray();
    }

    // ──────────────────────────────────────────────
    // Time aggregation
    // ──────────────────────────────────────────────

    public function getEventsOverTime(string $eventType, string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        // PostgreSQL TO_CHAR date formatting
        $format = match ($groupBy) {
            'month' => 'YYYY-MM',
            'week'  => 'IYYY-IW',
            default => 'YYYY-MM-DD',
        };

        return DB::table('usage_events')
            ->where('event_type', $eventType)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->select(
                DB::raw("TO_CHAR(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    // ──────────────────────────────────────────────
    // Per-entity stats
    // ──────────────────────────────────────────────

    public function getEntityStats(string $entityIri, string $startDate, string $endDate): array
    {
        $range = [$startDate, $endDate . ' 23:59:59'];

        $views = (int) DB::table('usage_events')
            ->where('entity_iri', $entityIri)
            ->where('event_type', 'view')
            ->whereBetween('created_at', $range)
            ->count();

        $downloads = (int) DB::table('usage_events')
            ->where('entity_iri', $entityIri)
            ->where('event_type', 'download')
            ->whereBetween('created_at', $range)
            ->count();

        $searches = (int) DB::table('usage_events')
            ->where('entity_iri', $entityIri)
            ->where('event_type', 'search')
            ->whereBetween('created_at', $range)
            ->count();

        $uniqueVisitors = (int) DB::table('usage_events')
            ->where('entity_iri', $entityIri)
            ->whereBetween('created_at', $range)
            ->distinct('ip_address')
            ->count('ip_address');

        // Daily breakdown
        $daily = DB::table('usage_events')
            ->where('entity_iri', $entityIri)
            ->whereBetween('created_at', $range)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as date"),
                'event_type',
                DB::raw('COUNT(*) as event_count')
            )
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'views'           => $views,
            'downloads'       => $downloads,
            'searches'        => $searches,
            'unique_visitors' => $uniqueVisitors,
            'daily'           => $daily,
        ];
    }

    // ──────────────────────────────────────────────
    // Aggregation
    // ──────────────────────────────────────────────

    public function aggregateStats(): void
    {
        $this->aggregateDaily();
        $this->aggregateMonthly();
    }

    /**
     * Aggregate raw events into statistics_daily rows.
     * Processes all dates not yet aggregated.
     */
    protected function aggregateDaily(): void
    {
        $lastAggregated = DB::table('statistics_daily')
            ->max('date');

        $startDate = $lastAggregated
            ? date('Y-m-d', strtotime($lastAggregated . ' +1 day'))
            : DB::table('usage_events')->min(DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD')"));

        if ($startDate === null) {
            return;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($startDate > $yesterday) {
            return;
        }

        $rows = DB::table('usage_events')
            ->whereBetween(DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD')"), [$startDate, $yesterday])
            ->select(
                'entity_iri',
                DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as date"),
                DB::raw("COUNT(*) FILTER (WHERE event_type = 'view') as views"),
                DB::raw("COUNT(*) FILTER (WHERE event_type = 'download') as downloads"),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('entity_iri', 'date')
            ->get();

        foreach ($rows as $row) {
            DB::table('statistics_daily')->updateOrInsert(
                [
                    'entity_iri' => $row->entity_iri,
                    'date'       => $row->date,
                ],
                [
                    'views'           => $row->views,
                    'downloads'       => $row->downloads,
                    'unique_visitors' => $row->unique_visitors,
                ]
            );
        }
    }

    /**
     * Aggregate raw events into statistics_monthly rows.
     */
    protected function aggregateMonthly(): void
    {
        $lastAggregated = DB::table('statistics_monthly')
            ->max('year_month');

        $startMonth = $lastAggregated
            ? date('Y-m', strtotime($lastAggregated . '-01 +1 month'))
            : DB::table('usage_events')->min(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"));

        if ($startMonth === null) {
            return;
        }

        $lastMonth = date('Y-m', strtotime('-1 month'));
        if ($startMonth > $lastMonth) {
            return;
        }

        $rows = DB::table('usage_events')
            ->whereBetween(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"), [$startMonth, $lastMonth])
            ->select(
                'entity_iri',
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as year_month"),
                DB::raw("COUNT(*) FILTER (WHERE event_type = 'view') as views"),
                DB::raw("COUNT(*) FILTER (WHERE event_type = 'download') as downloads"),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('entity_iri', 'year_month')
            ->get();

        foreach ($rows as $row) {
            DB::table('statistics_monthly')->updateOrInsert(
                [
                    'entity_iri' => $row->entity_iri,
                    'year_month' => $row->year_month,
                ],
                [
                    'views'           => $row->views,
                    'downloads'       => $row->downloads,
                    'unique_visitors' => $row->unique_visitors,
                ]
            );
        }
    }

    // ──────────────────────────────────────────────
    // CSV export
    // ──────────────────────────────────────────────

    public function exportCsv(string $type, string $startDate, string $endDate): StreamedResponse
    {
        return response()->streamDownload(function () use ($type, $startDate, $endDate) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            switch ($type) {
                case 'views':
                    fputcsv($out, ['Period', 'Views', 'Unique Visitors']);
                    $data = $this->getEventsOverTime('view', $startDate, $endDate);
                    foreach ($data as $row) {
                        fputcsv($out, [
                            $row->period ?? $row['period'],
                            $row->event_count ?? $row['event_count'],
                            $row->unique_visitors ?? $row['unique_visitors'],
                        ]);
                    }
                    break;

                case 'downloads':
                    fputcsv($out, ['Period', 'Downloads', 'Unique Visitors']);
                    $data = $this->getEventsOverTime('download', $startDate, $endDate);
                    foreach ($data as $row) {
                        fputcsv($out, [
                            $row->period ?? $row['period'],
                            $row->event_count ?? $row['event_count'],
                            $row->unique_visitors ?? $row['unique_visitors'],
                        ]);
                    }
                    break;

                case 'top-views':
                    fputcsv($out, ['Entity IRI', 'Entity Type', 'Views', 'Unique Visitors']);
                    $data = $this->getTopItems('view', 500, $startDate, $endDate);
                    foreach ($data as $row) {
                        fputcsv($out, [$row->entity_iri, $row->entity_type, $row->event_count, $row->unique_visitors]);
                    }
                    break;

                case 'top-downloads':
                    fputcsv($out, ['Entity IRI', 'Entity Type', 'Downloads', 'Unique Visitors']);
                    $data = $this->getTopItems('download', 500, $startDate, $endDate);
                    foreach ($data as $row) {
                        fputcsv($out, [$row->entity_iri, $row->entity_type, $row->event_count, $row->unique_visitors]);
                    }
                    break;

                case 'geographic':
                    fputcsv($out, ['Country', 'Events', 'Unique Visitors', 'Unique Entities']);
                    $data = $this->getGeographicStats($startDate, $endDate);
                    foreach ($data as $row) {
                        fputcsv($out, [
                            $row->country ?? $row['country'],
                            $row->event_count ?? $row['event_count'],
                            $row->unique_visitors ?? $row['unique_visitors'],
                            $row->unique_entities ?? $row['unique_entities'],
                        ]);
                    }
                    break;

                default:
                    fputcsv($out, ['Metric', 'Value']);
                    $stats = $this->getDashboardStats($startDate, $endDate);
                    foreach ($stats as $metric => $value) {
                        fputcsv($out, [$metric, $value]);
                    }
                    break;
            }

            fclose($out);
        }, "statistics_{$type}_{$startDate}_{$endDate}.csv", ['Content-Type' => 'text/csv']);
    }

    // ──────────────────────────────────────────────
    // Bot filtering
    // ──────────────────────────────────────────────

    public function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }

        // Check against known bot patterns stored in database
        $patterns = DB::table('bot_patterns')
            ->where('is_active', true)
            ->pluck('pattern');

        foreach ($patterns as $pattern) {
            // PostgreSQL ILIKE-style matching: use case-insensitive strpos for in-memory check
            if (mb_stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Fallback: common bot signatures
        $commonBots = [
            'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider',
            'YandexBot', 'Sogou', 'facebookexternalhit', 'Twitterbot',
            'rogerbot', 'linkedinbot', 'embedly', 'quora link preview',
            'showyoubot', 'outbrain', 'pinterest', 'applebot', 'SemrushBot',
            'AhrefsBot', 'MJ12bot', 'DotBot', 'PetalBot', 'AspiegelBot',
            'crawler', 'spider', 'bot/', 'bot;', 'HeadlessChrome',
        ];

        $userAgentLower = mb_strtolower($userAgent);
        foreach ($commonBots as $bot) {
            if (mb_stripos($userAgentLower, mb_strtolower($bot)) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getBotList(): Collection
    {
        return DB::table('bot_patterns')
            ->orderBy('name')
            ->get();
    }

    public function addBot(array $data): int
    {
        return DB::table('bot_patterns')->insertGetId([
            'name'       => $data['name'] ?? '',
            'pattern'    => $data['pattern'] ?? '',
            'category'   => $data['category'] ?? 'general',
            'is_active'  => true,
            'created_at' => now(),
        ]);
    }

    public function deleteBot(int $id): void
    {
        DB::table('bot_patterns')->where('id', $id)->delete();
    }

    // ──────────────────────────────────────────────
    // Configuration
    // ──────────────────────────────────────────────

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $row = DB::table('settings')
            ->where('setting_group', 'statistics')
            ->where('setting_key', $key)
            ->first();

        if ($row === null) {
            return $default;
        }

        $value = $row->setting_value;

        // Cast boolean-like strings
        if ($value === '1' || $value === 'true') {
            return true;
        }
        if ($value === '0' || $value === 'false') {
            return false;
        }

        return $value;
    }

    public function setConfig(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['setting_group' => 'statistics', 'setting_key' => $key],
            ['setting_value' => (string) $value, 'updated_at' => now()]
        );
    }

    // ──────────────────────────────────────────────
    // Purge
    // ──────────────────────────────────────────────

    public function purgeOldEvents(int $days): int
    {
        $cutoff = now()->subDays($days)->toDateString();

        return DB::table('usage_events')
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    // ──────────────────────────────────────────────
    // GeoIP helpers
    // ──────────────────────────────────────────────

    /**
     * Resolve country/city from IP address using MaxMind GeoIP2.
     *
     * @return array{country: string|null, city: string|null}
     */
    protected function resolveGeoIp(string $ipAddress): array
    {
        $dbPath = (string) $this->getConfig('geoip_database_path', '/usr/share/GeoIP/GeoLite2-City.mmdb');

        if (!file_exists($dbPath)) {
            return ['country' => null, 'city' => null];
        }

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $record = $reader->city($ipAddress);

            return [
                'country' => $record->country->isoCode,
                'city'    => $record->city->name,
            ];
        } catch (\Throwable) {
            return ['country' => null, 'city' => null];
        }
    }

    /**
     * Anonymize an IP address by zeroing the last octet (IPv4) or last 80 bits (IPv6).
     */
    protected function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (string) preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Zero the last 5 groups (80 bits)
            $parts = explode(':', $ip);
            $count = count($parts);
            for ($i = max(0, $count - 5); $i < $count; $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }

        return $ip;
    }
}
