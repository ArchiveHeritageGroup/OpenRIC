<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Contracts;

use Illuminate\Support\Collection;

interface ConditionServiceInterface
{
    public function assess(string $objectIri, array $data, int $userId): int;

    public function find(int $id): ?object;

    public function getLatest(string $objectIri): ?array;

    public function getHistory(string $objectIri): array;

    public function getUpcoming(int $days = 30): array;

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;

    public function getRecentChecks(int $limit = 20): array;

    public function getAdminStats(): array;

    public function getConditionBreakdown(): array;

    public function getPhotosForCheck(int $checkId): Collection;

    public function getPhoto(int $photoId): ?object;

    public function uploadPhoto(int $checkId, $file, string $photoType, string $caption, int $userId): int;

    public function deletePhoto(int $photoId, int $userId): bool;

    public function getAnnotations(int $photoId): array;

    public function saveAnnotations(int $photoId, array $annotations, int $userId): bool;

    public function getAnnotationStats(int $checkId): array;

    public function getTemplates(): array;

    public function getTemplate(int $id): ?array;

    public function generateReport(int $checkId): ?array;
}
