<?php

declare(strict_types=1);

namespace OpenRiC\Statistics\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Statistics\Contracts\StatisticsServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * StatisticsController — dashboard, views, downloads, top items, geographic, per-entity, export.
 *
 * Adapted from Heratio ahg-statistics StatisticsController (214 lines).
 * IRI-based entity references, PostgreSQL backend.
 */
class StatisticsController extends Controller
{
    protected StatisticsServiceInterface $service;

    public function __construct(StatisticsServiceInterface $service)
    {
        $this->service = $service;
    }

    // ──────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────

    public function dashboard(Request $request): View
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $stats = $this->service->getDashboardStats($startDate, $endDate);
        $topItems = $this->service->getTopItems('view', 10, $startDate, $endDate);
        $topDownloads = $this->service->getTopItems('download', 10, $startDate, $endDate);
        $geoStats = array_slice($this->service->getGeographicStats($startDate, $endDate), 0, 10);
        $viewsOverTime = $this->service->getEventsOverTime('view', $startDate, $endDate);
        $downloadsOverTime = $this->service->getEventsOverTime('download', $startDate, $endDate);

        return view('statistics::dashboard', compact(
            'stats',
            'topItems',
            'topDownloads',
            'geoStats',
            'viewsOverTime',
            'downloadsOverTime',
            'startDate',
            'endDate'
        ));
    }

    // ──────────────────────────────────────────────
    // Views over time
    // ──────────────────────────────────────────────

    public function views(Request $request): View
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());
        $groupBy = $request->get('group', 'day');

        $data = $this->service->getEventsOverTime('view', $startDate, $endDate, $groupBy);

        return view('statistics::views', compact('data', 'startDate', 'endDate', 'groupBy'));
    }

    // ──────────────────────────────────────────────
    // Downloads over time
    // ──────────────────────────────────────────────

    public function downloads(Request $request): View
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $data = $this->service->getEventsOverTime('download', $startDate, $endDate);
        $topDownloads = $this->service->getTopItems('download', 50, $startDate, $endDate);

        return view('statistics::downloads', compact('data', 'topDownloads', 'startDate', 'endDate'));
    }

    // ──────────────────────────────────────────────
    // Top items
    // ──────────────────────────────────────────────

    public function topItems(Request $request): View
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());
        $eventType = $request->get('type', 'view');
        $limit = min((int) $request->get('limit', 50), 500);

        $items = $this->service->getTopItems($eventType, $limit, $startDate, $endDate);

        return view('statistics::top-items', compact('items', 'startDate', 'endDate', 'eventType', 'limit'));
    }

    // ──────────────────────────────────────────────
    // Geographic
    // ──────────────────────────────────────────────

    public function geographic(Request $request): View
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $data = $this->service->getGeographicStats($startDate, $endDate);

        return view('statistics::geographic', compact('data', 'startDate', 'endDate'));
    }

    // ──────────────────────────────────────────────
    // Per-entity
    // ──────────────────────────────────────────────

    public function entity(Request $request): View
    {
        $entityIri = (string) $request->get('iri', '');
        abort_if($entityIri === '', 400, 'Entity IRI is required.');

        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $stats = $this->service->getEntityStats($entityIri, $startDate, $endDate);

        return view('statistics::entity', compact('entityIri', 'stats', 'startDate', 'endDate'));
    }

    // ──────────────────────────────────────────────
    // CSV export
    // ──────────────────────────────────────────────

    public function export(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'dashboard');
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        return $this->service->exportCsv($type, $startDate, $endDate);
    }

    // ──────────────────────────────────────────────
    // Admin settings
    // ──────────────────────────────────────────────

    public function admin(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $settings = [
                'retention_days',
                'geoip_enabled',
                'geoip_database_path',
                'bot_filtering_enabled',
                'anonymize_ip',
                'exclude_admin_views',
            ];

            foreach ($settings as $key) {
                $value = $request->get($key, '0');
                $this->service->setConfig($key, $value);
            }

            return redirect()->route('statistics.admin')->with('notice', 'Settings saved.');
        }

        $config = [
            'retention_days'        => $this->service->getConfig('retention_days', 90),
            'geoip_enabled'         => $this->service->getConfig('geoip_enabled', true),
            'geoip_database_path'   => $this->service->getConfig('geoip_database_path', '/usr/share/GeoIP/GeoLite2-City.mmdb'),
            'bot_filtering_enabled' => $this->service->getConfig('bot_filtering_enabled', true),
            'anonymize_ip'          => $this->service->getConfig('anonymize_ip', true),
            'exclude_admin_views'   => $this->service->getConfig('exclude_admin_views', true),
        ];

        $dbStats = [
            'raw_events'          => (int) \Illuminate\Support\Facades\DB::table('usage_events')->count(),
            'daily_aggregates'    => (int) \Illuminate\Support\Facades\DB::table('statistics_daily')->count(),
            'monthly_aggregates'  => (int) \Illuminate\Support\Facades\DB::table('statistics_monthly')->count(),
            'bot_patterns'        => (int) \Illuminate\Support\Facades\DB::table('bot_patterns')->count(),
        ];

        return view('statistics::admin', compact('config', 'dbStats'));
    }

    // ──────────────────────────────────────────────
    // Bot management
    // ──────────────────────────────────────────────

    public function bots(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $action = $request->get('form_action');

            if ($action === 'add') {
                $this->service->addBot($request->only(['name', 'pattern', 'category']));
            } elseif ($action === 'delete') {
                $this->service->deleteBot((int) $request->get('id'));
            }

            return redirect()->route('statistics.bots')->with('notice', 'Bot list updated.');
        }

        $bots = $this->service->getBotList();

        return view('statistics::bots', compact('bots'));
    }

    // ──────────────────────────────────────────────
    // Maintenance actions
    // ──────────────────────────────────────────────

    public function post(Request $request): RedirectResponse
    {
        $action = $request->get('action');

        if ($action === 'purge') {
            $days = (int) $request->get('days', 90);
            $deleted = $this->service->purgeOldEvents($days);

            return redirect()->route('statistics.admin')
                ->with('notice', "Purged {$deleted} events older than {$days} days.");
        }

        if ($action === 'aggregate') {
            $this->service->aggregateStats();

            return redirect()->route('statistics.admin')
                ->with('notice', 'Statistics aggregated successfully.');
        }

        return redirect()->back()->with('error', 'Invalid action.');
    }
}
