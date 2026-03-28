<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use OpenRiC\AgentManage\Contracts\FamilyServiceInterface;

/**
 * Family service — extends AgentService with rico:Family type.
 * Adapted from Heratio ActorService (1,531 lines) filtered to entity_type_id = Family.
 */
class FamilyService extends AgentService implements FamilyServiceInterface
{
    private const RDF_TYPE = 'rico:Family';

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        return $this->browseByType(self::RDF_TYPE, $filters, $limit, $offset);
    }

    public function find(string $iri): ?array
    {
        return $this->findAgent($iri);
    }

    public function create(array $data, string $userId, string $reason): string
    {
        return $this->createAgent(self::RDF_TYPE, $data, $userId, $reason);
    }

    public function update(string $iri, array $data, string $userId, string $reason): bool
    {
        return $this->updateAgent($iri, $data, $userId, $reason);
    }

    public function delete(string $iri, string $userId, string $reason): bool
    {
        return $this->deleteAgent($iri, $userId, $reason);
    }
}
