<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Provenance\Contracts\DescriptionHistoryServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class DescriptionHistoryService implements DescriptionHistoryServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function getHistory(string $entityIri, int $limit = 50): array
    {
        $rdfHistory = $this->getRdfStarProvenance($entityIri, $limit);
        $auditHistory = $this->getAuditHistory($entityIri, $limit);

        $combined = array_merge($rdfHistory, $auditHistory);

        usort($combined, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        return array_slice($combined, 0, $limit);
    }

    public function getRdfStarProvenance(string $entityIri, int $limit = 50): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?predicate ?object ?modifiedBy ?modifiedAt ?changeReason WHERE {
                << ?entityIri ?predicate ?object >> openric:modifiedBy ?modifiedBy .
                OPTIONAL { << ?entityIri ?predicate ?object >> openric:modifiedAt ?modifiedAt }
                OPTIONAL { << ?entityIri ?predicate ?object >> openric:changeReason ?changeReason }
            }
            ORDER BY DESC(?modifiedAt)
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, [
            'entityIri' => $entityIri,
            'limit' => (string) $limit,
        ]);

        return array_map(fn ($row) => [
            'source' => 'rdf-star',
            'timestamp' => $row['modifiedAt']['value'] ?? '',
            'user' => $row['modifiedBy']['value'] ?? '',
            'action' => 'triple_write',
            'details' => [
                'predicate' => $row['predicate']['value'] ?? '',
                'object' => $row['object']['value'] ?? '',
                'reason' => $row['changeReason']['value'] ?? '',
            ],
        ], $results);
    }

    public function getAuditHistory(string $entityId, int $limit = 50): array
    {
        $entries = DB::table('audit_log')
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $entries->map(fn ($entry) => [
            'source' => 'audit_log',
            'timestamp' => $entry->created_at,
            'user' => $entry->username ?? 'System',
            'action' => $entry->action,
            'details' => [
                'entity_type' => $entry->entity_type,
                'entity_title' => $entry->entity_title,
                'old_values' => $entry->old_values ? json_decode($entry->old_values, true) : null,
                'new_values' => $entry->new_values ? json_decode($entry->new_values, true) : null,
                'changed_fields' => $entry->changed_fields ? json_decode($entry->changed_fields, true) : null,
                'description' => $entry->description,
            ],
        ])->toArray();
    }

    public function getDescriptionRecords(string $entityIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?descRecord ?title ?formType ?createdDate ?creator WHERE {
                ?descRecord rico:describesOrDescribed ?entityIri .
                ?descRecord a rico:Record .
                OPTIONAL { ?descRecord rico:title ?title }
                OPTIONAL { ?descRecord rico:hasDocumentaryFormType ?formType }
                OPTIONAL { ?descRecord rico:hasCreationDate ?createdDate }
                OPTIONAL { ?descRecord rico:hasOrHadCreator ?creator }
            }
            LIMIT 50
            SPARQL;

        return $this->triplestore->select($sparql, ['entityIri' => $entityIri]);
    }
}
