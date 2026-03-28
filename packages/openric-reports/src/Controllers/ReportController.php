<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Reports\Contracts\ReportServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportController — dashboard, entity reports, user activity, CSV export.
 *
 * Adapted from Heratio ahg-reports ReportController (723 lines).
 */
class ReportController extends Controller
{
    protected ReportServiceInterface $service;

    public function __construct(ReportServiceInterface $service)
    {
        $this->service = $service;
    }

    public function dashboard(): View
    {
        $stats = $this->service->getDashboardStats();
        $creationStats = $this->service->getCreationStats('month');

        return view('reports::dashboard', compact('stats', 'creationStats'));
    }

    public function collections(Request $request): View|StreamedResponse
    {
        $data = $this->service->getCollectionStats();

        if ($request->input('export') === 'csv') {
            return $this->service->exportReport('collections', $data, 'collection-report.csv');
        }

        return view('reports::collections', compact('data'));
    }

    public function users(Request $request): View|StreamedResponse
    {
        $data = $this->service->getUserStats();

        if ($request->input('export') === 'csv') {
            return $this->service->exportReport('users', $data['top_users'] ?? [], 'user-report.csv');
        }

        return view('reports::users', compact('data'));
    }

    public function access(Request $request): View|StreamedResponse
    {
        $data = $this->service->getAccessStats();

        if ($request->input('export') === 'csv') {
            $rows = [];
            foreach ($data['rights_by_basis'] ?? [] as $basis => $count) {
                $rows[] = ['basis' => $basis, 'count' => $count];
            }
            return $this->service->exportReport('access', $rows, 'access-report.csv');
        }

        return view('reports::access', compact('data'));
    }

    public function search(Request $request): View|StreamedResponse
    {
        $data = $this->service->getSearchStats();

        if ($request->input('export') === 'csv') {
            return $this->service->exportReport('search', $data['top_search_terms'] ?? [], 'search-report.csv');
        }

        return view('reports::search', compact('data'));
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'dashboard');

        $data = match ($type) {
            'collections' => $this->service->getCollectionStats(),
            'users'       => $this->service->getUserStats()['top_users'] ?? [],
            'search'      => $this->service->getSearchStats()['top_search_terms'] ?? [],
            default       => [['metric' => 'total_entities', 'value' => $this->service->getDashboardStats()['total_entities'] ?? 0]],
        };

        return $this->service->exportReport($type, $data, "{$type}-report.csv");
    }
}
