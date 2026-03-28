<?php

declare(strict_types=1);

namespace OpenRiC\DropdownManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dropdown management controller -- adapted from Heratio AhgDropdownManage\Controllers\DropdownController (428 lines).
 *
 * Provides the admin UI for the DropdownService already in openric-core.
 * Manages taxonomies, terms, section groupings, and field-column mappings.
 */
class DropdownManageController extends Controller
{
    /**
     * Section labels for taxonomy grouping.
     */
    protected array $sectionLabels = [
        'access_research'    => 'Access & Research',
        'ai'                 => 'AI & Automation',
        'condition'          => 'Condition & Conservation',
        'core'               => 'Core & System',
        'digital_media'      => 'Digital Assets & Media',
        'display_ui'         => 'Display & UI',
        'export_import'      => 'Export & Import',
        'forms_metadata'     => 'Forms & Metadata',
        'people'             => 'People & Organisations',
        'preservation'       => 'Preservation',
        'provenance_rights'  => 'Provenance & Rights',
        'reporting_workflow' => 'Reporting & Workflow',
        'other'              => 'Other',
    ];

    protected array $sectionIcons = [
        'access_research'    => 'fa-book-reader',
        'ai'                 => 'fa-robot',
        'condition'          => 'fa-clipboard-check',
        'core'               => 'fa-cogs',
        'digital_media'      => 'fa-photo-video',
        'display_ui'         => 'fa-desktop',
        'export_import'      => 'fa-file-export',
        'forms_metadata'     => 'fa-file-alt',
        'people'             => 'fa-users',
        'preservation'       => 'fa-shield-alt',
        'provenance_rights'  => 'fa-balance-scale',
        'reporting_workflow' => 'fa-tasks',
        'other'              => 'fa-folder',
    ];

    /**
     * List all taxonomies grouped by section.
     */
    public function taxonomies(): JsonResponse
    {
        $rows = DB::table('dropdowns')
            ->select('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->selectRaw('COUNT(*) as term_count')
            ->where('is_active', true)
            ->groupBy('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->orderBy('taxonomy_label')
            ->get();

        $bySection = [];
        foreach ($rows as $row) {
            $section = $row->taxonomy_section ?: 'other';
            $bySection[$section][] = $row;
        }

        // Sort sections by defined order
        $ordered = [];
        foreach (array_keys($this->sectionLabels) as $key) {
            if (isset($bySection[$key])) {
                $ordered[$key] = $bySection[$key];
            }
        }
        foreach ($bySection as $key => $items) {
            if (!isset($ordered[$key])) {
                $ordered[$key] = $items;
            }
        }

        return response()->json([
            'sections'      => $ordered,
            'sectionLabels' => $this->sectionLabels,
            'sectionIcons'  => $this->sectionIcons,
        ]);
    }

    /**
     * List all terms for a given taxonomy.
     */
    public function values(string $taxonomy): JsonResponse
    {
        $terms = DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        if ($terms->isEmpty()) {
            return response()->json(['error' => 'Taxonomy not found.'], 404);
        }

        $columnMappings = DB::table('dropdown_column_map')
            ->where('taxonomy', $taxonomy)
            ->orderBy('table_name')
            ->orderBy('column_name')
            ->get();

        return response()->json([
            'taxonomy'       => $taxonomy,
            'taxonomyLabel'  => $terms->first()->taxonomy_label,
            'terms'          => $terms,
            'columnMappings' => $columnMappings,
        ]);
    }

    /**
     * Create a new term in a taxonomy.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy' => 'required|string|max:100',
            'label'    => 'required|string|max:255',
            'code'     => 'required|string|max:100',
            'color'    => 'nullable|string|max:7',
            'icon'     => 'nullable|string|max:50',
        ]);

        $exists = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->where('code', $request->input('code'))
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'A term with this code already exists in this taxonomy.'], 422);
        }

        $existing = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->first();

        if (!$existing) {
            return response()->json(['error' => 'Taxonomy not found.'], 404);
        }

        $maxSort = DB::table('dropdowns')
            ->where('taxonomy', $request->input('taxonomy'))
            ->max('sort_order') ?? -1;

        $id = DB::table('dropdowns')->insertGetId([
            'taxonomy'         => $request->input('taxonomy'),
            'taxonomy_label'   => $existing->taxonomy_label,
            'taxonomy_section' => $existing->taxonomy_section,
            'code'             => $request->input('code'),
            'label'            => $request->input('label'),
            'color'            => $request->input('color'),
            'icon'             => $request->input('icon'),
            'sort_order'       => $maxSort + 1,
            'is_default'       => false,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $term = DB::table('dropdowns')->where('id', $id)->first();

        return response()->json(['term' => $term], 201);
    }

    /**
     * Update a single term.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'field' => 'required|string|in:label,color,icon,is_active,is_default',
            'value' => 'nullable|string|max:255',
        ]);

        $field = $request->input('field');
        $value = $request->input('value');

        if ($field === 'is_active' || $field === 'is_default') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        if ($field === 'is_default' && $value) {
            $term = DB::table('dropdowns')->where('id', $id)->first();
            if ($term) {
                DB::table('dropdowns')
                    ->where('taxonomy', $term->taxonomy)
                    ->update(['is_default' => false, 'updated_at' => now()]);
            }
        }

        $updated = DB::table('dropdowns')
            ->where('id', $id)
            ->update([$field => $value, 'updated_at' => now()]);

        if ($updated === 0) {
            return response()->json(['error' => 'Term not found or no change.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a term.
     */
    public function destroy(int $id): JsonResponse
    {
        $term = DB::table('dropdowns')->where('id', $id)->first();

        if (!$term) {
            return response()->json(['error' => 'Term not found.'], 404);
        }

        DB::table('dropdowns')->where('id', $id)->delete();

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

        return response()->json(['success' => true]);
    }

    /**
     * Reorder terms within a taxonomy.
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

        return response()->json(['success' => true]);
    }

    /**
     * List field-column mappings.
     */
    public function mappings(): JsonResponse
    {
        $mappings = DB::table('dropdown_column_map')
            ->orderBy('taxonomy')
            ->orderBy('table_name')
            ->orderBy('column_name')
            ->get();

        return response()->json(['mappings' => $mappings]);
    }

    /**
     * Create a new field-column mapping.
     */
    public function createMapping(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomy'    => 'required|string|max:100',
            'table_name'  => 'required|string|max:100',
            'column_name' => 'required|string|max:100',
        ]);

        $exists = DB::table('dropdown_column_map')
            ->where('taxonomy', $request->input('taxonomy'))
            ->where('table_name', $request->input('table_name'))
            ->where('column_name', $request->input('column_name'))
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Mapping already exists.'], 422);
        }

        $id = DB::table('dropdown_column_map')->insertGetId([
            'taxonomy'    => $request->input('taxonomy'),
            'table_name'  => $request->input('table_name'),
            'column_name' => $request->input('column_name'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['id' => $id], 201);
    }

    /**
     * Delete a field-column mapping.
     */
    public function destroyMapping(int $id): JsonResponse
    {
        $deleted = DB::table('dropdown_column_map')->where('id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['error' => 'Mapping not found.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
