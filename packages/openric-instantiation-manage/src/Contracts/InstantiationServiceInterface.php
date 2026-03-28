<?php

declare(strict_types=1);

namespace OpenRiC\InstantiationManage\Contracts;

interface InstantiationServiceInterface
{
    /** @return array{items: array, total: int} */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;

    public function find(string $iri): ?array;

    public function create(array $data, string $userId, string $reason): string;

    public function update(string $iri, array $data, string $userId, string $reason): bool;

    public function delete(string $iri, string $userId, string $reason): bool;

    /** @return array{items: array, total: int} */
    public function getForRecord(string $recordIri, int $limit = 25, int $offset = 0): array;

    /** @return array<int, array{iri: string, label: string, count: int}> */
    public function getCarrierTypes(): array;

    /** @return array<int, array{iri: string, label: string, count: int}> */
    public function getRepresentationTypes(): array;
}
