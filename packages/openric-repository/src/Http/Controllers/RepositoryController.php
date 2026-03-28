<?php

declare(strict_types=1);

namespace OpenRiC\Repository\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenRiC\Repository\Services\RepositoryBrowseService;
use OpenRiC\Repository\Services\RepositoryService;

/**
 * Full controller for archival-institution (repository) management — ISDIAH compliant.
 *
 * Adapted from Heratio ahg-repository-manage RepositoryController.
 * Provides browse (with facets), show, create, edit, delete, print, and autocomplete.
 */
class RepositoryController extends Controller
{
    protected RepositoryService $service;

    public function __construct()
    {
        $this->service = new RepositoryService(app()->getLocale());
    }

    // =====================================================================
    //  BROWSE
    // =====================================================================

    /**
     * Faceted browse view for repositories.
     */
    public function browse(Request $request)
    {
        $culture = app()->getLocale();
        $browseService = new RepositoryBrowseService($culture);

        $params = [
            'page'             => $request->get('page', 1),
            'limit'            => $request->get('limit', config('openric.hits_per_page', 30)),
            'sort'             => $request->get('sort', 'lastUpdated'),
            'sortDir'          => $request->get('sortDir', ''),
            'subquery'         => $request->get('subquery', ''),
            'thematicArea'     => $request->get('thematicAreas', ''),
            'region'           => $request->get('regions', $request->get('region', '')),
            'locality'         => $request->get('locality', ''),
            'hasDigitalObject' => $request->get('hasDigitalObject', ''),
            'archiveType'      => $request->get('types', $request->get('archiveType', '')),
            'subregion'        => $request->get('geographicSubregions', $request->get('subregion', '')),
            'languages'        => $request->get('languages', ''),
        ];

        $hasAdvanced = $params['thematicArea'] || $params['region']
            || $params['locality'] || $params['hasDigitalObject']
            || $params['archiveType'] || $params['subregion']
            || $params['languages'];

        $result = $hasAdvanced
            ? $browseService->browseAdvanced($params)
            : $browseService->browse($params);

        $thematicAreaFacets = $browseService->getThematicAreaFacets();
        $regions            = $browseService->getRegionFacets();
        $archiveTypeFacets  = $browseService->getArchiveTypeFacets();
        $subregionFacets    = $browseService->getSubregionFacets();
        $languageFacets     = $browseService->getLanguageFacets();
        $localityFacets     = $browseService->getLocalityFacets();

        // Thematic area options for advanced search dropdown (full list from taxonomy)
        $thematicAreaOptions = DB::table('term')
            ->join('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                  ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 72)
            ->orderBy('term_i18n.name')
            ->select('term.id', 'term_i18n.name')
            ->get();

        // Repository types for advanced search dropdown
        $repositoryTypes = DB::table('term')
            ->join('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                  ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', 37)
            ->orderBy('term_i18n.name')
            ->select('term.id', 'term_i18n.name')
            ->get();

        // ── Filter tags (active filter pills) ──
        $filterTags = [];

        if (!empty($params['thematicArea'])) {
            $taName = DB::table('term_i18n')
                ->where('id', $params['thematicArea'])
                ->where('culture', $culture)
                ->value('name');
            if ($taName) {
                $filterTags[] = [
                    'label'     => 'Thematic area: ' . $taName,
                    'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['thematicAreas', 'page'])),
                ];
            }
        }

        if (!empty($params['archiveType'])) {
            $atName = DB::table('term_i18n')
                ->where('id', $params['archiveType'])
                ->where('culture', $culture)
                ->value('name');
            if ($atName) {
                $filterTags[] = [
                    'label'     => 'Archive type: ' . $atName,
                    'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['types', 'page'])),
                ];
            }
        }

        if (!empty($params['region'])) {
            $filterTags[] = [
                'label'     => 'Region: ' . $params['region'],
                'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['regions', 'page'])),
            ];
        }

        if (!empty($params['subregion'])) {
            $srName = DB::table('term_i18n')
                ->where('id', $params['subregion'])
                ->where('culture', $culture)
                ->value('name');
            if ($srName) {
                $filterTags[] = [
                    'label'     => 'Subregion: ' . $srName,
                    'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['geographicSubregions', 'page'])),
                ];
            }
        }

        if (!empty($params['locality'])) {
            $filterTags[] = [
                'label'     => 'Locality: ' . $params['locality'],
                'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['locality', 'page'])),
            ];
        }

        if (!empty($params['languages'])) {
            $langDisplay = locale_get_display_language($params['languages'], 'en') ?: $params['languages'];
            $filterTags[] = [
                'label'     => 'Language: ' . ucfirst($langDisplay),
                'removeUrl' => url('/repository/browse') . '?' . http_build_query($request->except(['languages', 'page'])),
            ];
        }

        return view('repository::index', [
            'result'               => $result,
            'thematicAreaFacets'   => $thematicAreaFacets,
            'regions'              => $regions,
            'archiveTypeFacets'    => $archiveTypeFacets,
            'subregionFacets'      => $subregionFacets,
            'languageFacets'       => $languageFacets,
            'localityFacets'       => $localityFacets,
            'thematicAreaOptions'  => $thematicAreaOptions,
            'repositoryTypes'      => $repositoryTypes,
            'params'               => $params,
            'filterTags'           => $filterTags,
            'sortOptions'          => [
                'lastUpdated' => 'Date modified',
                'alphabetic'  => 'Name',
                'identifier'  => 'Identifier',
            ],
        ]);
    }

    // =====================================================================
    //  SHOW
    // =====================================================================

    /**
     * Full repository detail view with all ISDIAH sections.
     */
    public function show(string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $contacts            = $this->service->getContacts($repository->id);
        $digitalObjects      = $this->service->getDigitalObjects($repository->id);
        $holdingsCount       = $this->service->getHoldingsCount($repository->id);
        $descStatusName      = $this->service->getTermName($repository->desc_status_id);
        $descDetailName      = $this->service->getTermName($repository->desc_detail_id);
        $otherNames          = $this->service->getOtherNames($repository->id);
        $repositoryTypes     = $this->service->getRepositoryTypes($repository->id);
        $languages           = $this->service->getLanguages($repository->id);
        $scripts             = $this->service->getScripts($repository->id);
        $maintenanceNotes    = $this->service->getMaintenanceNotes($repository->id);
        $thematicAreas       = $this->service->getThematicAreas($repository->id);
        $geographicSubregions = $this->service->getGeographicSubregions($repository->id);

        // Sidebar: paginated holdings list
        $holdingsPage  = (int) request('holdings_page', 1);
        $holdingsPager = $this->service->getHoldingsPaginated($repository->id, 10, $holdingsPage);
        $holdings      = $holdingsPager->getCollection();

        // Sidebar: maintained actors
        $maintainedActorsList = $this->service->getMaintainedActors($repository->id, 10, (int) request('actors_page', 1));

        // Source language name
        $sourceLangName = null;
        if ($repository->source_culture ?? null) {
            $langNames = [
                'en' => 'English', 'fr' => 'French', 'es' => 'Spanish', 'pt' => 'Portuguese',
                'de' => 'German', 'nl' => 'Dutch', 'it' => 'Italian', 'af' => 'Afrikaans',
                'zu' => 'Zulu', 'xh' => 'Xhosa', 'st' => 'Southern Sotho', 'tn' => 'Tswana',
                'ar' => 'Arabic', 'ja' => 'Japanese', 'zh' => 'Chinese',
            ];
            $sourceLangName = $langNames[$repository->source_culture] ?? $repository->source_culture;
        }

        return view('repository::show', [
            'repository'            => $repository,
            'contacts'              => $contacts,
            'digitalObjects'        => $digitalObjects,
            'holdingsCount'         => $holdingsCount,
            'holdings'              => $holdings,
            'holdingsPager'         => $holdingsPager,
            'maintainedActorsList'  => $maintainedActorsList,
            'descStatusName'        => $descStatusName,
            'descDetailName'        => $descDetailName,
            'otherNames'            => $otherNames,
            'repositoryTypes'       => $repositoryTypes,
            'languages'             => $languages,
            'scripts'               => $scripts,
            'maintenanceNotes'      => $maintenanceNotes,
            'thematicAreas'         => $thematicAreas,
            'geographicSubregions'  => $geographicSubregions,
            'sourceLangName'        => $sourceLangName,
        ]);
    }

    // =====================================================================
    //  PRINT
    // =====================================================================

    /**
     * Print-friendly view for a repository.
     */
    public function print(string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $contacts            = $this->service->getContacts($repository->id);
        $holdingsCount       = $this->service->getHoldingsCount($repository->id);
        $descStatusName      = $this->service->getTermName($repository->desc_status_id);
        $descDetailName      = $this->service->getTermName($repository->desc_detail_id);
        $otherNames          = $this->service->getOtherNames($repository->id);
        $repositoryTypes     = $this->service->getRepositoryTypes($repository->id);
        $languages           = $this->service->getLanguages($repository->id);
        $scripts             = $this->service->getScripts($repository->id);
        $maintenanceNotes    = $this->service->getMaintenanceNotes($repository->id);
        $thematicAreas       = $this->service->getThematicAreas($repository->id);
        $geographicSubregions = $this->service->getGeographicSubregions($repository->id);

        return view('repository::print', [
            'repository'            => $repository,
            'contacts'              => $contacts,
            'holdingsCount'         => $holdingsCount,
            'descStatusName'        => $descStatusName,
            'descDetailName'        => $descDetailName,
            'otherNames'            => $otherNames,
            'repositoryTypes'       => $repositoryTypes,
            'languages'             => $languages,
            'scripts'               => $scripts,
            'maintenanceNotes'      => $maintenanceNotes,
            'thematicAreas'         => $thematicAreas,
            'geographicSubregions'  => $geographicSubregions,
        ]);
    }

    // =====================================================================
    //  CREATE / EDIT
    // =====================================================================

    /**
     * Show the create form (empty).
     */
    public function create()
    {
        $formChoices = $this->service->getFormChoices();

        return view('repository::edit', [
            'repository'       => null,
            'contacts'         => collect(),
            'formChoices'      => $formChoices,
            'maintenanceNotes' => null,
            'parallelNames'    => collect(),
            'otherNames'       => collect(),
        ]);
    }

    /**
     * Show the edit form for an existing repository.
     */
    public function edit(string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $contacts         = $this->service->getContacts($repository->id);
        $formChoices      = $this->service->getFormChoices();
        $maintenanceNotes = $this->service->getMaintenanceNotes($repository->id);
        $otherNamesAll    = $this->service->getOtherNames($repository->id);

        // type_id 148 = Parallel form, type_id 149 = Other form
        $parallelNames = $otherNamesAll->where('type_id', 148);
        $otherNames    = $otherNamesAll->where('type_id', 149);

        return view('repository::edit', [
            'repository'       => $repository,
            'contacts'         => $contacts,
            'formChoices'      => $formChoices,
            'maintenanceNotes' => $maintenanceNotes,
            'parallelNames'    => $parallelNames,
            'otherNames'       => $otherNames,
        ]);
    }

    /**
     * Store a new repository.
     */
    public function store(Request $request)
    {
        $request->validate([
            'authorized_form_of_name' => 'required|string|max:1024',
            'identifier'              => 'nullable|string|max:1024',
        ]);

        $data = $request->only($this->getAllFields());

        $id   = $this->service->create($data);
        $slug = $this->service->getSlug($id);

        return redirect()
            ->route('repository.show', $slug)
            ->with('success', 'Repository created successfully.');
    }

    /**
     * Update an existing repository.
     */
    public function update(Request $request, string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $request->validate([
            'authorized_form_of_name' => 'required|string|max:1024',
            'identifier'              => 'nullable|string|max:1024',
        ]);

        $data = $request->only($this->getAllFields());

        $this->service->update($repository->id, $data);

        return redirect()
            ->route('repository.show', $slug)
            ->with('success', 'Repository updated successfully.');
    }

    // =====================================================================
    //  DELETE
    // =====================================================================

    /**
     * Confirm-delete view.
     */
    public function confirmDelete(string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $holdingsCount = $this->service->getHoldingsCount($repository->id);

        return view('repository::show', [
            'repository'    => $repository,
            'holdingsCount' => $holdingsCount,
            'confirmDelete' => true,
            'contacts'              => collect(),
            'digitalObjects'        => [],
            'holdings'              => collect(),
            'holdingsPager'         => null,
            'maintainedActorsList'  => null,
            'descStatusName'        => null,
            'descDetailName'        => null,
            'otherNames'            => collect(),
            'repositoryTypes'       => collect(),
            'languages'             => [],
            'scripts'               => [],
            'maintenanceNotes'      => null,
            'thematicAreas'         => collect(),
            'geographicSubregions'  => collect(),
            'sourceLangName'        => null,
        ]);
    }

    /**
     * Delete a repository.
     */
    public function destroy(Request $request, string $slug)
    {
        $repository = $this->service->getBySlug($slug);
        if (!$repository) {
            abort(404);
        }

        $this->service->delete($repository->id);

        return redirect()
            ->route('repository.browse')
            ->with('success', 'Repository deleted successfully.');
    }

    // =====================================================================
    //  AUTOCOMPLETE (JSON)
    // =====================================================================

    /**
     * Autocomplete for repository names (JSON endpoint for AJAX lookups).
     */
    public function autocomplete(Request $request)
    {
        $query   = $request->get('query', '');
        $culture = app()->getLocale();
        $limit   = (int) $request->get('limit', 10);

        $results = DB::table('actor')
            ->join('actor_i18n', function ($j) use ($culture) {
                $j->on('actor.id', '=', 'actor_i18n.id')
                  ->where('actor_i18n.culture', '=', $culture);
            })
            ->join('repository', 'repository.id', '=', 'actor.id')
            ->join('slug', 'slug.object_id', '=', 'actor.id')
            ->where('actor_i18n.authorized_form_of_name', 'ILIKE', '%' . $query . '%')
            ->select(
                'actor.id',
                'actor_i18n.authorized_form_of_name as name',
                'slug.slug'
            )
            ->limit($limit)
            ->get();

        return response()->json($results);
    }

    // =====================================================================
    //  HELPERS
    // =====================================================================

    /**
     * All form field names accepted by store/update.
     */
    private function getAllFields(): array
    {
        return [
            // Actor i18n (ISAAR)
            'authorized_form_of_name', 'dates_of_existence', 'history', 'places',
            'legal_status', 'functions', 'mandates', 'internal_structures',
            'general_context', 'institution_responsible_identifier', 'rules',
            'sources', 'revision_history',
            // Repository
            'identifier', 'desc_status_id', 'desc_detail_id', 'desc_identifier', 'upload_limit',
            // Repository i18n (ISDIAH)
            'geocultural_context', 'collecting_policies', 'buildings', 'holdings',
            'finding_aids', 'opening_times', 'access_conditions', 'disabled_access',
            'research_services', 'reproduction_services', 'public_facilities',
            'desc_institution_identifier', 'desc_rules', 'desc_sources', 'desc_revision_history',
            // Special fields (stored in other_name / note tables)
            'parallel_name', 'other_name', 'maintenance_notes',
            // Contacts
            'contacts',
        ];
    }
}
