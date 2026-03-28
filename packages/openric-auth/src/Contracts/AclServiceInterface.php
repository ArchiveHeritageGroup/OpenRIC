<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Contracts;

interface AclServiceInterface
{
    public function check(?string $entityType, string $action, ?int $userId = null): bool;

    public function canAdmin(?int $userId = null): bool;

    public function getUserPermissions(int $userId): array;

    public function hasPermission(int $userId, string $permission): bool;
}
