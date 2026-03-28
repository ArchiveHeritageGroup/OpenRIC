<?php

declare(strict_types=1);

namespace OpenRiC\CustomFields\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\CustomFields\Contracts\CustomFieldsServiceInterface;

/**
 * Custom fields service -- adapted from Heratio AhgCustomFields\Services\CustomFieldService (84 lines).
 */
class CustomFieldsService implements CustomFieldsServiceInterface
{
    public function getFieldsForEntity(string $entityType): Collection
    {
        return DB::table('custom_field_definitions')
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public function getField(int $fieldId): ?object
    {
        return DB::table('custom_field_definitions')
            ->where('id', $fieldId)
            ->first();
    }

    public function createField(array $data): int
    {
        $maxSort = DB::table('custom_field_definitions')
            ->where('entity_type', $data['entity_type'] ?? '')
            ->max('sort_order') ?? -1;

        return DB::table('custom_field_definitions')->insertGetId([
            'name'        => $data['name'],
            'label'       => $data['label'] ?? $data['name'],
            'field_type'  => $data['field_type'] ?? 'text',
            'entity_type' => $data['entity_type'],
            'options'     => isset($data['options']) ? json_encode($data['options'], JSON_THROW_ON_ERROR) : null,
            'is_required' => $data['is_required'] ?? false,
            'sort_order'  => $data['sort_order'] ?? ($maxSort + 1),
            'is_active'   => $data['is_active'] ?? true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function updateField(int $fieldId, array $data): bool
    {
        $allowed = ['name', 'label', 'field_type', 'entity_type', 'is_required', 'sort_order', 'is_active'];

        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        if (array_key_exists('options', $data)) {
            $update['options'] = is_array($data['options'])
                ? json_encode($data['options'], JSON_THROW_ON_ERROR)
                : $data['options'];
        }

        $update['updated_at'] = now();

        return DB::table('custom_field_definitions')
            ->where('id', $fieldId)
            ->update($update) > 0;
    }

    public function deleteField(int $fieldId): bool
    {
        DB::table('custom_field_values')->where('field_id', $fieldId)->delete();

        return DB::table('custom_field_definitions')
            ->where('id', $fieldId)
            ->delete() > 0;
    }

    public function getFieldValue(int $fieldId, string $entityIri): ?string
    {
        return DB::table('custom_field_values')
            ->where('field_id', $fieldId)
            ->where('entity_iri', $entityIri)
            ->value('value');
    }

    public function setFieldValue(int $fieldId, string $entityIri, ?string $value): void
    {
        if ($value === null || $value === '') {
            DB::table('custom_field_values')
                ->where('field_id', $fieldId)
                ->where('entity_iri', $entityIri)
                ->delete();
            return;
        }

        DB::table('custom_field_values')->updateOrInsert(
            ['field_id' => $fieldId, 'entity_iri' => $entityIri],
            ['value' => $value, 'updated_at' => now()],
        );
    }

    public function getEntityValues(string $entityIri): Collection
    {
        return DB::table('custom_field_values')
            ->join('custom_field_definitions', 'custom_field_values.field_id', '=', 'custom_field_definitions.id')
            ->where('custom_field_values.entity_iri', $entityIri)
            ->select(
                'custom_field_definitions.*',
                'custom_field_values.value',
                'custom_field_values.id as value_id',
            )
            ->orderBy('custom_field_definitions.sort_order')
            ->get();
    }
}
