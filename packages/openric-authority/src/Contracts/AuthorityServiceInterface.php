<?php

declare(strict_types=1);

namespace OpenRiC\Authority\Contracts;

interface AuthorityServiceInterface
{
    public function searchWikidata(string $name, string $agentType = 'person'): array;

    public function searchViaf(string $name, string $agentType = 'person'): array;

    public function searchLcnaf(string $name): array;

    public function linkAgent(string $agentIri, string $externalUri, string $source, string $userId, string $reason): bool;

    public function getExternalLinks(string $iri): array;
}
