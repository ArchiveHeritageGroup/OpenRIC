<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for preservation (AIP / PREMIS) operations.
 * Adapted from Heratio PreservationService.
 */
interface PreservationServiceInterface
{
    public function getAipsForRecord(int $recordId): Collection;
    public function getPremisObjects(int $recordId): Collection;
    public function getAipDetails(int $aipId): ?object;
}
