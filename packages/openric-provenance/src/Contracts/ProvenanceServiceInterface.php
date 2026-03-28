<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Contracts;

interface ProvenanceServiceInterface
{
    public function createActivity(string $activityType, array $data, string $userId, string $reason): string;

    public function getTimeline(string $entityIri, int $limit = 50): array;

    public function getCustodyChain(string $entityIri): array;

    public function getActivitiesForEntity(string $entityIri, int $limit = 50): array;

    public function createDescriptionRecord(string $describedEntityIri, string $userId, string $reason): string;

    public function getActivityTypes(): array;
}
