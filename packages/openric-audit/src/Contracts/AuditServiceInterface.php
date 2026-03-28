<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Contracts;

interface AuditServiceInterface
{
    public function log(string $action, array $data): void;

    public function logCreate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $newValues = null): void;

    public function logUpdate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null, ?array $newValues = null): void;

    public function logDelete(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null): void;

    public function logAuth(string $action, ?string $description = null): void;

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;

    public function find(int $id): ?array;
}
