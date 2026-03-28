<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Heritage\Contracts\HeritageServiceInterface;

/**
 * HeritageController — heritage object CRUD and analytics dashboard.
 *
 * Adapted from Heratio ahg-heritage-manage HeritageController (700+ lines).
 */
class HeritageController extends Controller
{
    protected HeritageServiceInterface $service;

    public function __construct(HeritageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function dashboard(): View
    {
        $analytics = $this->service->getAnalytics();
        $recent = $this->service->browse(['sort' => 'date', 'limit' => 10]);

        return view('heritage::dashboard', compact('analytics', 'recent'));
    }

    public function browse(Request $request): View
    {
        $params = [
            'page'  => (int) $request->input('page', 1),
            'limit' => (int) $request->input('limit', 20),
            'type'  => $request->input('type'),
            'query' => $request->input('query'),
            'sort'  => $request->input('sort', 'title'),
        ];

        $result = $this->service->browse($params);
        $stats = $this->service->getStats();

        return view('heritage::browse', array_merge($result, [
            'params' => $params,
            'stats'  => $stats,
        ]));
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if (!$entity) {
            abort(404, 'Heritage object not found.');
        }

        return view('heritage::show', compact('entity'));
    }

    public function create(): View
    {
        $custodians = $this->service->getCustodians();

        return view('heritage::create', compact('custodians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'         => 'required|string|max:1024',
            'heritage_type' => 'required|string|max:255',
            'description'   => 'nullable|string',
        ]);

        $iri = $this->service->create(
            $request->only([
                'title', 'heritage_type', 'description', 'identifier',
                'custodian_iri', 'extent', 'location', 'condition', 'date_created',
            ]),
            (string) auth()->id()
        );

        return redirect()
            ->route('heritage.show', ['iri' => $iri])
            ->with('success', 'Heritage object created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if (!$entity) {
            abort(404);
        }

        $custodians = $this->service->getCustodians();

        return view('heritage::edit', compact('entity', 'custodians'));
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:1024',
        ]);

        $this->service->update(
            $iri,
            $request->only([
                'title', 'heritage_type', 'description', 'identifier',
                'custodian_iri', 'extent', 'location', 'condition',
            ]),
            (string) auth()->id()
        );

        return redirect()
            ->route('heritage.show', ['iri' => $iri])
            ->with('success', 'Heritage object updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $this->service->delete($iri, (string) auth()->id());

        return redirect()
            ->route('heritage.browse')
            ->with('success', 'Heritage object deleted.');
    }
}
