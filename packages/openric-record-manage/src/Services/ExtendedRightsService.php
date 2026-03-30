<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\ExtendedRightsServiceInterface;

/**
 * Extended rights, embargo, TK labels, Creative Commons licenses.
 * Adapted from Heratio ExtendedRightsService (669 lines).
 *
 * Tables: record_rights, extended_rights, extended_rights_tk_labels, embargoes,
 *         rights_statements, rights_cc_licenses, rights_tk_labels
 */
class ExtendedRightsService implements ExtendedRightsServiceInterface
{
    public function getRightsForRecord(int $recordId): Collection
    {
        try {
            return DB::table('record_rights')
                ->where('record_id', $recordId)
                ->orderByDesc('created_at')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getExtendedRights(int $recordId): Collection
    {
        try {
            return DB::table('extended_rights as er')
                ->leftJoin('rights_statements as rs', 'er.rights_statement_id', '=', 'rs.id')
                ->leftJoin('rights_cc_licenses as cc', 'er.creative_commons_license_id', '=', 'cc.id')
                ->where('er.record_id', $recordId)
                ->select([
                    'er.*',
                    'rs.code as rights_statement_code', 'rs.uri as rights_statement_uri',
                    'rs.name as rights_statement_name',
                    'cc.code as cc_license_code', 'cc.uri as cc_license_uri',
                ])
                ->orderByDesc('er.is_primary')
                ->orderByDesc('er.created_at')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getTkLabelsForRights(int $extendedRightsId): Collection
    {
        try {
            return DB::table('extended_rights_tk_labels as ertl')
                ->join('rights_tk_labels as tkl', 'ertl.tk_label_id', '=', 'tkl.id')
                ->where('ertl.extended_rights_id', $extendedRightsId)
                ->select('tkl.*')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function saveExtendedRight(int $recordId, array $data, ?int $userId = null): int
    {
        $now = now();

        if (!empty($data['is_primary'])) {
            DB::table('extended_rights')
                ->where('record_id', $recordId)->where('is_primary', true)
                ->update(['is_primary' => false, 'updated_at' => $now]);
        }

        $id = DB::table('extended_rights')->insertGetId([
            'record_id'                   => $recordId,
            'rights_statement_id'         => $data['rights_statement_id'] ?? null,
            'creative_commons_license_id' => $data['creative_commons_license_id'] ?? null,
            'rights_date'                 => $data['rights_date'] ?? null,
            'expiry_date'                 => $data['expiry_date'] ?? null,
            'rights_holder'               => $data['rights_holder'] ?? null,
            'rights_holder_uri'           => $data['rights_holder_uri'] ?? null,
            'rights_note'                 => $data['rights_note'] ?? null,
            'usage_conditions'            => $data['usage_conditions'] ?? null,
            'copyright_notice'            => $data['copyright_notice'] ?? null,
            'is_primary'                  => $data['is_primary'] ?? true,
            'created_by'                  => $userId,
            'updated_by'                  => $userId,
            'created_at'                  => $now,
            'updated_at'                  => $now,
        ]);

        if (!empty($data['tk_label_ids'])) {
            foreach ($data['tk_label_ids'] as $tkLabelId) {
                DB::table('extended_rights_tk_labels')->insert([
                    'extended_rights_id' => $id,
                    'tk_label_id'        => (int) $tkLabelId,
                    'created_at'         => $now,
                ]);
            }
        }

        return $id;
    }

    public function updateExtendedRight(int $rightsId, array $data, ?int $userId = null): void
    {
        $now = now();

        DB::table('extended_rights')->where('id', $rightsId)->update([
            'rights_statement_id'         => $data['rights_statement_id'] ?? null,
            'creative_commons_license_id' => $data['creative_commons_license_id'] ?? null,
            'rights_date'                 => $data['rights_date'] ?? null,
            'expiry_date'                 => $data['expiry_date'] ?? null,
            'rights_holder'               => $data['rights_holder'] ?? null,
            'rights_holder_uri'           => $data['rights_holder_uri'] ?? null,
            'rights_note'                 => $data['rights_note'] ?? null,
            'usage_conditions'            => $data['usage_conditions'] ?? null,
            'copyright_notice'            => $data['copyright_notice'] ?? null,
            'is_primary'                  => $data['is_primary'] ?? true,
            'updated_by'                  => $userId,
            'updated_at'                  => $now,
        ]);

        DB::table('extended_rights_tk_labels')->where('extended_rights_id', $rightsId)->delete();
        if (!empty($data['tk_label_ids'])) {
            foreach ($data['tk_label_ids'] as $tkLabelId) {
                DB::table('extended_rights_tk_labels')->insert([
                    'extended_rights_id' => $rightsId,
                    'tk_label_id'        => (int) $tkLabelId,
                    'created_at'         => $now,
                ]);
            }
        }
    }

    public function deleteExtendedRight(int $rightsId): void
    {
        DB::table('extended_rights_tk_labels')->where('extended_rights_id', $rightsId)->delete();
        DB::table('extended_rights')->where('id', $rightsId)->delete();
    }

    public function getActiveEmbargo(int $recordId): ?object
    {
        try {
            return DB::table('embargoes')
                ->where('record_id', $recordId)->where('is_active', true)
                ->where('start_date', '<=', now()->toDateString())
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
                ->orderByDesc('created_at')
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }

    public function getAllEmbargoes(int $recordId): Collection
    {
        try {
            return DB::table('embargoes')
                ->where('record_id', $recordId)
                ->orderByDesc('created_at')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function createEmbargo(array $data): int
    {
        $startDate = $data['start_date'] ?? now()->toDateString();

        return DB::table('embargoes')->insertGetId([
            'record_id'          => $data['record_id'],
            'embargo_type'       => $data['embargo_type'],
            'start_date'         => $startDate,
            'end_date'           => $data['end_date'] ?? null,
            'reason'             => $data['reason'] ?? null,
            'is_perpetual'       => $data['is_perpetual'] ?? false,
            'is_active'          => true,
            'status'             => strtotime($startDate) <= time() ? 'active' : 'pending',
            'created_by'         => $data['created_by'] ?? null,
            'notify_on_expiry'   => $data['notify_on_expiry'] ?? true,
            'notify_days_before' => $data['notify_days_before'] ?? 30,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function liftEmbargo(int $id, int $userId, string $reason): bool
    {
        return DB::table('embargoes')->where('id', $id)->update([
            'is_active'   => false,
            'status'      => 'lifted',
            'lifted_by'   => $userId,
            'lifted_at'   => now(),
            'lift_reason'  => $reason,
            'updated_at'  => now(),
        ]) > 0;
    }

    public function createEmbargoWithPropagation(array $data, bool $applyToChildren = false): array
    {
        $results = ['created' => 0, 'failed' => 0, 'ids' => []];

        try {
            $embargoId = $this->createEmbargo($data);
            $results['created']++;
            $results['ids'][] = $embargoId;
        } catch (\Exception $e) {
            $results['failed']++;
            return $results;
        }

        if ($applyToChildren) {
            $descendants = DB::table('records')
                ->where('parent_id', $data['record_id'])
                ->pluck('id')->toArray();

            foreach ($descendants as $childId) {
                try {
                    $childData = $data;
                    $childData['record_id'] = $childId;
                    $childEmbargoId = $this->createEmbargo($childData);
                    $results['created']++;
                    $results['ids'][] = $childEmbargoId;
                } catch (\Exception $e) {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    public function getDescendantCount(int $recordId): int
    {
        try {
            return DB::table('records')
                ->where('parent_id', $recordId)
                ->whereNull('deleted_at')
                ->count();
        } catch (\Illuminate\Database\QueryException $e) {
            return 0;
        }
    }

    public function getRightsStatements(): Collection
    {
        try {
            return DB::table('rights_statements')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getCreativeCommonsLicenses(): Collection
    {
        try {
            return DB::table('rights_cc_licenses')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($item) {
                    $item->name = 'CC ' . strtoupper($item->code);
                    return $item;
                });
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getTkLabels(): Collection
    {
        try {
            return DB::table('rights_tk_labels')
                ->where('is_active', true)
                ->orderBy('category')->orderBy('sort_order')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getDonors(int $limit = 200): Collection
    {
        try {
            return DB::table('agents')
                ->whereNotNull('name')
                ->orderBy('name')
                ->limit($limit)
                ->select('id', 'name')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function exportJsonLd(int $recordId): array
    {
        $standardRights  = $this->getRightsForRecord($recordId);
        $extendedRights  = $this->getExtendedRights($recordId);
        $embargo         = $this->getActiveEmbargo($recordId);

        $record = DB::table('records')->where('id', $recordId)->first();
        $siteUrl = rtrim(config('app.url', ''), '/');

        $jsonLd = [
            '@context'   => [
                '@vocab'  => 'http://schema.org/',
                'rico'    => 'https://www.ica.org/standards/RiC/ontology#',
                'cc'      => 'http://creativecommons.org/ns#',
                'rs'      => 'http://rightsstatements.org/vocab/',
            ],
            '@type'      => 'rico:Record',
            '@id'        => $record->iri ?? ($siteUrl . '/records/' . $recordId),
            'identifier' => $record->identifier ?? null,
            'name'       => $record->title ?? null,
        ];

        if ($standardRights->isNotEmpty()) {
            $jsonLd['rights'] = $standardRights->map(fn ($r) => [
                '@type' => 'PropertyValue', 'value' => $r->rights_note ?? '',
                'startDate' => $r->start_date ?? null, 'endDate' => $r->end_date ?? null,
            ])->values()->toArray();
        }

        $primary = $extendedRights->firstWhere('is_primary', true);
        if ($primary && !empty($primary->rights_statement_uri)) {
            $jsonLd['dcterms:rights'] = [
                '@id' => $primary->rights_statement_uri, '@type' => 'dcterms:RightsStatement',
                'name' => $primary->rights_statement_name ?? $primary->rights_statement_code ?? null,
            ];
        }
        if ($primary && !empty($primary->cc_license_uri)) {
            $jsonLd['cc:license'] = [
                '@id' => $primary->cc_license_uri, '@type' => 'cc:License',
                'name' => $primary->cc_license_code ?? null,
            ];
        }
        if ($embargo) {
            $jsonLd['accessRestriction'] = [
                '@type' => 'PropertyValue', 'propertyID' => 'embargo',
                'value' => $embargo->embargo_type, 'startDate' => $embargo->start_date,
                'endDate' => $embargo->end_date, 'status' => $embargo->status,
            ];
        }

        return $jsonLd;
    }
}
