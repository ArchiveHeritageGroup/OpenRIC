<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Contracts;

interface ConditionServiceInterface
{
    public function assess(string $objectIri, array $data, int $userId): int;

    public function getLatest(string $objectIri): ?array;

    public function getHistory(string $objectIri): array;

    public function getUpcoming(int $days = 30): array;

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;
}
