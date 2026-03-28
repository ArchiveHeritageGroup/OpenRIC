<?php

declare(strict_types=1);

namespace OpenRiC\CustomFields\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\CustomFields\Contracts\CustomFieldsServiceInterface;

/**
 * Custom fields admin controller -- adapted from Heratio AhgCustomFields\Controllers\CustomFieldAdminController (166 lines).
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
    public function index(Request $request): JsonResponse
    {
        $entityType = $request->input('entity_type');

        if ($entityType) {
            $fields = $this->service->getFieldsForEntity($entityType);
        } else {
            $fields = \Illuminate\Support\Facades\DB::table('custom_field_definitions')
                ->orderBy('entity_type')
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json([
            'fields'      => $fields,
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
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'label'       => 'nullable|string|max:255',
            'field_type'  => 'required|string|in:' . implode(',', array_keys(self::FIELD_TYPES)),
            'entity_type' => 'required|string|in:' . implode(',', array_keys(self::ENTITY_TYPES)),
            'options'     => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $id = $this->service->createField($request->only([
            'name', 'label', 'field_type', 'entity_type', 'options', 'is_required', 'sort_order',
        ]));

        return response()->json(['id' => $id, 'message' => 'Custom field created.'], 201);
    }

    /**
     * Update a field definition.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'        => 'nullable|string|max:255',
            'label'       => 'nullable|string|max:255',
            'field_type'  => 'nullable|string|in:' . implode(',', array_keys(self::FIELD_TYPES)),
            'entity_type' => 'nullable|string|in:' . implode(',', array_keys(self::ENTITY_TYPES)),
            'options'     => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $updated = $this->service->updateField($id, $request->only([
            'name', 'label', 'field_type', 'entity_type', 'options', 'is_required', 'sort_order', 'is_active',
        ]));

        if (!$updated) {
            return response()->json(['error' => 'Field not found or no changes.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a field definition and all associated values.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteField($id);

        if (!$deleted) {
            return response()->json(['error' => 'Field not found.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
