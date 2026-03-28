<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

interface StandardsMappingServiceInterface
{
    /**
     * Render an entity's RiC-O properties as ISAD(G) elements.
     * @return array<string, array{label: string, value: string|null}>
     */
    public function renderIsadG(array $entityProperties): array;

    /**
     * Render an entity's RiC-O properties as ISAAR-CPF elements.
     * @return array<string, array{label: string, value: string|null}>
     */
    public function renderIsaarCpf(array $entityProperties): array;

    /**
     * Convert ISAD(G) form data to RiC-O properties for triplestore insert.
     * @return array<string, array{value: string, datatype?: string, type?: string}>
     */
    public function isadgToRico(array $formData): array;

    /**
     * Convert ISAAR-CPF form data to RiC-O properties.
     * @return array<string, array{value: string, datatype?: string, type?: string}>
     */
    public function isaarCpfToRico(array $formData): array;

    public function getIsadgMapping(): array;

    public function getIsaarCpfMapping(): array;
}
