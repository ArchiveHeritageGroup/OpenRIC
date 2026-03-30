<?php

declare(strict_types=1);

namespace OpenRiC\DropdownManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Dropdown management controller — adapted from Heratio ahg-dropdown-manage.
 *
 * Provides both page-rendering (index, edit) and AJAX endpoints for managing
 * controlled vocabularies used throughout OpenRiC: taxonomies, terms, sections,
 * field-column mappings, reordering, defaults, colours, and icons.
 */
class DropdownManageController extends Controller
{
    /**
     * Section labels for taxonomy grouping — matches Heratio ahgDropdownPlugin.
     */
    protected array $sectionLabels = [
        'access_research'    => 'Access & Research',
        'ai'                 => 'AI & Automation',
        'condition'          => 'Condition & Conservation',
        'core'               => 'Core & System',
        'digital_media'      => 'Digital Assets & Media',
        'display_ui'         => 'Display & UI',
        'donor_agreement'    => 'Donor Agreements',
        'exhibition_loan'    => 'Exhibitions & Loans',
        'export_import'      => 'Export & Import',
        'federation'         => 'Federation',
        'finance'            => 'Finance',
        'forms_metadata'     => 'Forms & Metadata',
        'heritage_monuments' => 'Heritage & Monuments',
        'integration'        => 'Integration',
        'people'             => 'People & Organisations',
        'preservation'       => 'Preservation',
        'privacy_compliance' => 'Privacy & Compliance',
        'provenance_rights'  => 'Provenance & Rights',
        'reporting_workflow' => 'Reporting & Workflow',
        'reproduction'       => 'Reproduction',
        'vendor'             => 'Vendor',
        'other'              => 'Other',
    ];

    /**
     * Section icons — matches Heratio ahgDropdownPlugin.
     */
    protected array $sectionIcons = [
        'access_research'    => 'fa-book-reader',
        'ai'                 => 'fa-robot',
        'condition'          => 'fa-clipboard-check',
        'core'               => 'fa-cogs',
        'digital_media'      => 'fa-photo-video',
        'display_ui'         => 'fa-desktop',
        'donor_agreement'    => 'fa-handshake',
        'exhibition_loan'    => 'fa-university',
        'export_import'      => 'fa-file-export',
        'federation'         => 'fa-project-diagram',
        'finance'            => 'fa-coins',
        'forms_metadata'     => 'fa-file-alt',
        'heritage_monuments' => 'fa-landmark',
        'integration'        => 'fa-plug',
        'people'             => 'fa-users',
        'preservation'       => 'fa-shield-alt',
        'privacy_compliance' => 'fa-user-shield',
        'provenance_rights'  => 'fa-balance-scale',
        'reporting_workflow' => 'fa-tasks',
        'reproduction'       => 'fa-copy',
        'vendor'             => 'fa-store',
        'other'              => 'fa-folder',
    ];

    /* ------------------------------------------------------------------ */
    /*  PAGE RENDERING                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Index: list all taxonomies grouped by section.
     */
    public function index(): View
    {
        $rows = DB::table('dropdowns')
            ->select('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->selectRaw('COUNT(*) as term_count')
            ->where('is_active', true)
            ->groupBy('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->orderBy('taxonomy_label')
            ->get();

        // Group by section
        $bySection = [];
        foreach ($rows as $row) {
            $section = $row->taxonomy_section ?: 'other';
            $bySection[$section][] = $row;
        }

        // Sort sections by the defined order
        $orderedSections = [];
        foreach (array_keys($this->sectionLabels) as $key) {
            if (isset($bySection[$key])) {
                $orderedSections[$key] = $bySection[$key];
            }
        }
        // Any sections not in the predefined list go at the end
        foreach ($bySection as $key => $items) {
            if (!isset($orderedSections[$key])) {
                $orderedSections[$key] = $items;
            }
        }

        return view('openric-dropdown-manage::index', [
            'sectionLabels'  => $this->sectionLabels,
            'sectionIcons'   => $this->sectionIcons,
            'taxonomyGroups' => $orderedSections,
        ]);
    }

    /**
     * Edit: list all terms for a given taxonomy.
     */
    public function edit(string $taxonomy): View
    {
        $terms = DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        if ($terms->isEmpty()) {
            abort(404, 'Taxonomy not found.');
        }

        $taxonomyLabel   = $terms->first()->taxonomy_label;
        $taxonomySection = $terms->first()->taxonomy_section;

        // Get column mappings for this taxonomy
        $columnMappings = DB::table('dropdown_column_map')
            ->where('taxonomy', $taxonomy)
            ->orderBy('table_name')
            ->orderBy('column_name')
            ->get();

        return view('openric-dropdown-manage::edit', [
            'taxonomy'        => $taxonomy,
            'taxonomyLabel'   => $taxonomyLabel,
            'taxonomySection' => $taxonomySection,
            'terms'           => $terms,
            'columnMappings'  => $columnMappings,
            'sectionLabels'   => $this->sectionLabels,
            'sectionIcons'    => $this->sectionIcons,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  TAXONOMY AJAX ENDPOINTS                                            */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX: Create a new taxonomy with an initial placeholder term.
     */
    public function createTaxonomy(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy_label'   => 'required|string|max:255',
            'taxonomy_code'    => 'required|string|max:100',
            'taxonomy_section' => 'required|string|max:50',
        ]);

        $code = Str::snake(Str::ascii($request->input('taxonomy_code')));

        // Check if taxonomy code already exists
        $exists = DB::table('dropdowns')->where('taxonomy', $code)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A taxonomy with this code already exists.',
            ], 422);
        }

        DB::table('dropdowns')->insert([
            'taxonomy'         => $code,
            'taxonomy_label'   => $request->input('taxonomy_label'),
            'taxonomy_section' => $request->input('taxonomy_section'),
            'code'             => 'default',
            'label'            => 'Default',
            'color'            => null,
            'icon'             => null,
            'sort_order'       => 0,
            'is_default'       => true,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json([
            'success'       => true,
            'message'       => 'Taxonomy created successfully.',
            'taxonomy_code' => $code,
        ]);
    }

    /**
     * AJAX: Rename a taxonomy's display label.
     */
    public function renameTaxonomy(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy'  => 'required|string|max:100',
            'new_label' => 'required|string|max:255',
        ]);

        $updated = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->update([
                'taxonomy_label' => $request->input('new_label'),
                'updated_at'     => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Taxonomy not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Taxonomy renamed successfully.']);
    }

    /**
     * AJAX: Delete an entire taxonomy (all its terms).
     */
    public function deleteTaxonomy(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy' => 'required|string|max:100',
        ]);

        $deleted = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->delete();

        if ($deleted === 0) {
            return response()->json(['success' => false, 'message' => 'Taxonomy not found.'], 404);
        }

        // Also remove column mappings for this taxonomy
        DB::table('dropdown_column_map')
            ->where('taxonomy', $request->input('taxonomy'))
            ->delete();

        return response()->json(['success' => true, 'message' => 'Taxonomy and all its terms deleted.']);
    }

    /**
     * AJAX: Move a taxonomy to a different section.
     */
    public function moveSection(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy' => 'required|string|max:100',
            'section'  => 'required|string|max:50',
        ]);

        $updated = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->update([
                'taxonomy_section' => $request->input('section'),
                'updated_at'       => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Taxonomy not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Taxonomy moved to new section.']);
    }

    /* ------------------------------------------------------------------ */
    /*  TERM AJAX ENDPOINTS                                                */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX: Add a term to a taxonomy.
     */
    public function addTerm(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy' => 'required|string|max:100',
            'label'    => 'required|string|max:255',
            'code'     => 'required|string|max:100',
            'color'    => 'nullable|string|max:7',
            'icon'     => 'nullable|string|max:50',
        ]);

        // Check code uniqueness within taxonomy
        $exists = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->where('code', $request->input('code'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A term with this code already exists in this taxonomy.',
            ], 422);
        }

        // Get taxonomy metadata from existing terms
        $existing = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->first();

        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Taxonomy not found.'], 404);
        }

        // Determine next sort_order
        $maxSort = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->max('sort_order') ?? -1;

        $id = DB::table('dropdowns')->insertGetId([
            'taxonomy'         => $request->input('taxonomy'),
            'taxonomy_label'   => $existing->taxonomy_label,
            'taxonomy_section' => $existing->taxonomy_section,
            'code'             => $request->input('code'),
            'label'            => $request->input('label'),
            'color'            => $request->input('color') ?: null,
            'icon'             => $request->input('icon') ?: null,
            'sort_order'       => $maxSort + 1,
            'is_default'       => false,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $term = DB::table('dropdowns')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Term added successfully.',
            'term'    => $term,
        ]);
    }

    /**
     * AJAX: Update a single field on a term.
     */
    public function updateTerm(Request $request): JsonResponse
    {
        $request->validate([
            'id'    => 'required|integer',
            'field' => 'required|string|in:label,color,icon,is_active',
            'value' => 'nullable|string|max:255',
        ]);

        $field = $request->input('field');
        $value = $request->input('value');

        // Convert boolean-like values for is_active
        if ($field === 'is_active') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $updated = DB::table('dropdowns')
            ->where('id', $request->input('id'))
            ->update([
                $field       => $value,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Term not found or no change.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Term updated.']);
    }

    /**
     * AJAX: Delete a term.
     */
    public function deleteTerm(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $term = DB::table('dropdowns')->where('id', $request->input('id'))->first();

        if (!$term) {
            return response()->json(['success' => false, 'message' => 'Term not found.'], 404);
        }

        DB::table('dropdowns')->where('id', $request->input('id'))->delete();

        // If deleted term was the default, set the first remaining term as default
        if ($term->is_default) {
            $first = DB::table('dropdowns')
                ->where('taxonomy', $term->taxonomy)
                ->orderBy('sort_order')
                ->first();
            if ($first) {
                DB::table('dropdowns')
                    ->where('id', $first->id)
                    ->update(['is_default' => true, 'updated_at' => now()]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Term deleted.']);
    }

    /**
     * AJAX: Reorder terms within a taxonomy.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($request->input('ids') as $index => $id) {
            DB::table('dropdowns')
                ->where('id', $id)
                ->update(['sort_order' => $index, 'updated_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Terms reordered.']);
    }

    /**
     * AJAX: Set a term as the default for its taxonomy.
     */
    public function setDefault(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $term = DB::table('dropdowns')->where('id', $request->input('id'))->first();

        if (!$term) {
            return response()->json(['success' => false, 'message' => 'Term not found.'], 404);
        }

        // Clear existing default for this taxonomy
        DB::table('dropdowns')
            ->where('taxonomy', $term->taxonomy)
            ->update(['is_default' => false, 'updated_at' => now()]);

        // Set the new default
        DB::table('dropdowns')
            ->where('id', $request->input('id'))
            ->update(['is_default' => true, 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Default term updated.']);
    }

    /* ------------------------------------------------------------------ */
    /*  COLUMN MAPPING ENDPOINTS                                           */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX: List all field-column mappings.
     */
    public function mappings(): JsonResponse
    {
        $mappings = DB::table('dropdown_column_map')
            ->orderBy('taxonomy')
            ->orderBy('table_name')
            ->orderBy('column_name')
            ->get();

        return response()->json(['success' => true, 'mappings' => $mappings]);
    }

    /**
     * AJAX: Create a new field-column mapping.
     */
    public function createMapping(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy'    => 'required|string|max:100',
            'table_name'  => 'required|string|max:100',
            'column_name' => 'required|string|max:100',
            'is_strict'   => 'nullable|boolean',
        ]);

        $exists = DB::table('dropdown_column_map')
            ->where('taxonomy', $request->input('taxonomy'))
            ->where('table_name', $request->input('table_name'))
            ->where('column_name', $request->input('column_name'))
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Mapping already exists.'], 422);
        }

        $id = DB::table('dropdown_column_map')->insertGetId([
            'taxonomy'    => $request->input('taxonomy'),
            'table_name'  => $request->input('table_name'),
            'column_name' => $request->input('column_name'),
            'is_strict'   => $request->boolean('is_strict', false),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Mapping created.', 'id' => $id]);
    }

    /**
     * AJAX: Delete a field-column mapping.
     */
    public function destroyMapping(int $id): JsonResponse
    {
        $deleted = DB::table('dropdown_column_map')->where('id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['success' => false, 'message' => 'Mapping not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Mapping deleted.']);
    }
}
