<?php

declare(strict_types=1);

namespace OpenRiC\CustomFields\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\CustomFields\Contracts\CustomFieldsServiceInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom fields admin controller -- adapted from Heratio AhgCustomFields\Controllers\CustomFieldAdminController (166 lines).
 *
 * Provides: index, create, edit, store, update, destroy, export CSV, import CSV, reorder (AJAX).
 */
class CustomFieldsController extends Controller
{
    private const ENTITY_TYPES = [
        'RecordResource' => 'Record Resource',
        'Agent'          => 'Agent / Authority',
        'Place'          => 'Place',
        'Activity'       => 'Activity',
        'Instantiation'  => 'Instantiation',
    ];

    private const FIELD_TYPES = [
        'text'       => 'Text (single line)',
        'textarea'   => 'Text (multi-line)',
        'number'     => 'Number',
        'date'       => 'Date',
        'select'     => 'Dropdown',
        'checkbox'   => 'Checkbox',
        'url'        => 'URL',
    ];

    public function __construct(
        private readonly CustomFieldsServiceInterface $service,
    ) {}

    /**
     * List all custom field definitions.
     */
    public function index(Request $request): View|JsonResponse
    {
        $entityType = $request->input('entity_type');

        if ($entityType) {
            $fields = $this->service->getFieldsForEntity($entityType);
        } else {
            $fields = DB::table('custom_field_definitions')
                ->orderBy('entity_type')
                ->orderBy('sort_order')
                ->get();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'fields'      => $fields,
                'entityTypes' => self::ENTITY_TYPES,
                'fieldTypes'  => self::FIELD_TYPES,
            ]);
        }

        return view('openric-custom-fields::admin.index', [
            'fields'      => $fields,
            'entityTypes' => self::ENTITY_TYPES,
            'fieldTypes'  => self::FIELD_TYPES,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('openric-custom-fields::admin.edit', [
            'field'       => null,
            'entityTypes' => self::ENTITY_TYPES,
            'fieldTypes'  => self::FIELD_TYPES,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $field = $this->service->getField($id);
        if (!$field) {
            abort(404, 'Custom field not found.');
        }

        return view('openric-custom-fields::admin.edit', [
            'field'       => $field,
            'entityTypes' => self::ENTITY_TYPES,
            'fieldTypes'  => self::FIELD_TYPES,
        ]);
    }

    /**
     * Show a single field definition.
     */
    public function show(int $id): JsonResponse
    {
        $field = $this->service->getField($id);

        if (!$field) {
            return response()->json(['error' => 'Custom field not found.'], 404);
        }

        return response()->json(['field' => $field]);
    }

    /**
     * Create a new field definition.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'label'       => 'nullable|string|max:255',
            'field_type'  => 'required|string|in:' . implode(',', array_keys(self::FIELD_TYPES)),
            'entity_type' => 'required|string|in:' . implode(',', array_keys(self::ENTITY_TYPES)),
            'options'     => 'nullable|string',
            'is_required' => 'nullable',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $data = $request->only(['name', 'label', 'field_type', 'entity_type', 'sort_order']);
        $data['is_required'] = $request->has('is_required');
        $data['is_active']   = $request->has('is_active');

        if ($request->has('options')) {
            $opts = array_filter(array_map('trim', explode("\n", $request->input('options', ''))));
            $data['options'] = !empty($opts) ? $opts : null;
        }

        $id = $this->service->createField($data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $id, 'message' => 'Custom field created.'], 201);
        }

        return redirect()->route('custom-fields.index')->with('success', 'Custom field created.');
    }

    /**
     * Update a field definition.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name'        => 'nullable|string|max:255',
            'label'       => 'nullable|string|max:255',
            'field_type'  => 'nullable|string|in:' . implode(',', array_keys(self::FIELD_TYPES)),
            'entity_type' => 'nullable|string|in:' . implode(',', array_keys(self::ENTITY_TYPES)),
            'options'     => 'nullable|string',
            'is_required' => 'nullable',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable',
        ]);

        $data = $request->only(['name', 'label', 'field_type', 'entity_type', 'sort_order']);
        $data['is_required'] = $request->has('is_required');
        $data['is_active']   = $request->has('is_active');

        if ($request->has('options')) {
            $opts = array_filter(array_map('trim', explode("\n", $request->input('options', ''))));
            $data['options'] = !empty($opts) ? $opts : null;
        }

        $updated = $this->service->updateField($id, $data);

        if ($request->expectsJson()) {
            if (!$updated) {
                return response()->json(['error' => 'Field not found or no changes.'], 404);
            }
            return response()->json(['success' => true]);
        }

        return redirect()->route('custom-fields.index')->with('success', 'Custom field updated.');
    }

    /**
     * Delete a field definition and all associated values.
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $deleted = $this->service->deleteField($id);

        if (!$deleted) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Field not found.'], 404);
            }
            return redirect()->route('custom-fields.index')->with('error', 'Field not found.');
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('custom-fields.index')->with('success', 'Custom field deleted.');
    }

    /**
     * Export custom field definitions as CSV.
     */
    public function export(): Response
    {
        $fields = DB::table('custom_field_definitions')
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->get();

        $output = fopen('php://temp', 'r+');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['ID', 'Name', 'Label', 'Field Type', 'Entity Type', 'Is Required', 'Is Active', 'Sort Order']);

        foreach ($fields as $def) {
            fputcsv($output, [
                $def->id ?? '',
                $def->name ?? '',
                $def->label ?? '',
                $def->field_type ?? '',
                $def->entity_type ?? '',
                $def->is_required ?? 0,
                $def->is_active ?? 1,
                $def->sort_order ?? 0,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="custom_fields_export.csv"',
        ]);
    }

    /**
     * Import custom field definitions from CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($rows);

        $imported = 0;
        foreach ($rows as $row) {
            if (count($row) >= 4) {
                $this->service->createField([
                    'name'        => $row[1] ?? '',
                    'label'       => $row[2] ?? $row[1] ?? '',
                    'field_type'  => $row[3] ?? 'text',
                    'entity_type' => $row[4] ?? 'RecordResource',
                    'is_required' => (bool) ($row[5] ?? false),
                    'is_active'   => (bool) ($row[6] ?? true),
                    'sort_order'  => (int) ($row[7] ?? 0),
                ]);
                $imported++;
            }
        }

        return redirect()->route('custom-fields.index')->with('success', "{$imported} custom field(s) imported.");
    }

    /**
     * Reorder custom field definitions (AJAX).
     */
    public function reorder(Request $request): JsonResponse
    {
        $order = $request->input('order', []);

        foreach ($order as $item) {
            $this->service->updateField((int) $item['id'], ['sort_order' => (int) $item['sort']]);
        }

        return response()->json(['success' => true]);
    }
}
