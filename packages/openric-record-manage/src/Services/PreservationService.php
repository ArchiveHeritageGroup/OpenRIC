<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\PreservationServiceInterface;

/**
 * Preservation service — AIP packages and PREMIS objects.
 * Adapted from Heratio PreservationService (108 lines).
 *
 * Tables: aips, premis_objects
 */
class PreservationService implements PreservationServiceInterface
{
    public function getAipsForRecord(int $recordId): Collection
    {
        try {
            return DB::table('aips')
                ->where('record_id', $recordId)
                ->orderByDesc('created_at')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getPremisObjects(int $recordId): Collection
    {
        try {
            return DB::table('premis_objects')
                ->where('record_id', $recordId)
                ->orderByDesc('date_ingested')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getAipDetails(int $aipId): ?object
    {
        try {
            $aip = DB::table('aips')
                ->where('id', $aipId)
                ->first();

            if ($aip && $aip->record_id) {
                $aip->premis_objects = DB::table('premis_objects')
                    ->where('record_id', $aip->record_id)
                    ->get();
            }

            return $aip;
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }
}
