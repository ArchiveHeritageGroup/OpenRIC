<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\AiGovernance\Contracts\AiRightsServiceInterface;

/**
 * AI Rights & Restrictions Matrix — Module 2.
 *
 * Machine-readable per-entity and per-collection AI use policies.
 * Resolution order: entity-specific → collection-level → global default.
 */
class AiRightsService implements AiRightsServiceInterface
{
    public function getRestriction(int $id): ?object
    {
        return DB::table('ai_rights_restrictions')->where('id', $id)->first();
    }

    public function getRestrictionForEntity(string $entityIri): ?object
    {
        return DB::table('ai_rights_restrictions')
            ->where('applies_to_iri', $entityIri)
            ->where('restriction_scope', 'entity')
            ->first();
    }

    /**
     * Resolve the effective restriction for an entity.
     * Priority: entity → collection → global.
     * Returns a default "all allowed" object if nothing is configured.
     */
    public function getEffectiveRestriction(string $entityIri): object
    {
        // 1. Entity-specific
        $restriction = DB::table('ai_rights_restrictions')
            ->where('applies_to_iri', $entityIri)
            ->where('restriction_scope', 'entity')
            ->where(function ($q): void {
                $q->whereNull('restriction_expires_at')
                  ->orWhere('restriction_expires_at', '>', now());
            })
            ->first();

        if ($restriction) {
            return $restriction;
        }

        // 2. Collection-level — check if entity belongs to a collection with restrictions
        $collectionRestriction = DB::table('ai_rights_restrictions')
            ->where('restriction_scope', 'collection')
            ->where(function ($q): void {
                $q->whereNull('restriction_expires_at')
                  ->orWhere('restriction_expires_at', '>', now());
            })
            ->orderByDesc('updated_at')
            ->first();

        if ($collectionRestriction) {
            return $collectionRestriction;
        }

        // 3. Global default
        $global = $this->getGlobalDefault();
        if ($global) {
            return $global;
        }

        // 4. Default: everything allowed
        return (object) [
            'id' => null,
            'applies_to_iri' => $entityIri,
            'restriction_scope' => 'default',
            'ai_allowed' => true,
            'summarisation_allowed' => true,
            'embedding_indexing_allowed' => true,
            'training_reuse_allowed' => false,
            'redaction_required_before_ai' => false,
            'rag_retrieval_allowed' => true,
            'translation_allowed' => true,
            'sensitivity_scan_allowed' => true,
            'restriction_notes' => null,
            'legal_basis' => null,
        ];
    }

    public function listRestrictions(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_rights_restrictions');

        if (!empty($filters['scope'])) {
            $query->where('restriction_scope', $filters['scope']);
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('applies_to_iri', 'ILIKE', $like)
                  ->orWhere('restriction_notes', 'ILIKE', $like)
                  ->orWhere('legal_basis', 'ILIKE', $like);
            });
        }
        if (isset($filters['ai_allowed'])) {
            $query->where('ai_allowed', (bool) $filters['ai_allowed']);
        }
        if (isset($filters['training_blocked'])) {
            $query->where('training_reuse_allowed', false);
        }

        $total = $query->count();
        $results = $query->orderByDesc('updated_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function createRestriction(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_rights_restrictions')->insertGetId($data);
    }

    public function updateRestriction(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('ai_rights_restrictions')->where('id', $id)->update($data);
    }

    public function deleteRestriction(int $id): void
    {
        DB::table('ai_rights_restrictions')->where('id', $id)->delete();
    }

    public function bulkApply(array $entityIris, array $restrictions): int
    {
        $affected = 0;
        $restrictions['updated_at'] = now();

        DB::transaction(function () use ($entityIris, $restrictions, &$affected): void {
            foreach ($entityIris as $iri) {
                $existing = DB::table('ai_rights_restrictions')
                    ->where('applies_to_iri', $iri)
                    ->where('restriction_scope', 'entity')
                    ->first();

                if ($existing) {
                    DB::table('ai_rights_restrictions')
                        ->where('id', $existing->id)
                        ->update($restrictions);
                } else {
                    DB::table('ai_rights_restrictions')->insert(array_merge($restrictions, [
                        'applies_to_iri' => $iri,
                        'restriction_scope' => 'entity',
                        'created_at' => now(),
                    ]));
                }
                $affected++;
            }
        });

        return $affected;
    }

    public function isAllowed(string $entityIri, string $operation): bool
    {
        $restriction = $this->getEffectiveRestriction($entityIri);

        if (!$restriction->ai_allowed) {
            return false;
        }

        return match ($operation) {
            'summarisation' => (bool) $restriction->summarisation_allowed,
            'embedding', 'indexing' => (bool) $restriction->embedding_indexing_allowed,
            'training', 'training_reuse' => (bool) $restriction->training_reuse_allowed,
            'rag', 'retrieval' => (bool) $restriction->rag_retrieval_allowed,
            'translation' => (bool) $restriction->translation_allowed,
            'sensitivity_scan' => (bool) $restriction->sensitivity_scan_allowed,
            default => (bool) $restriction->ai_allowed,
        };
    }

    public function getBlockedEntities(string $operation, int $limit = 100): array
    {
        $column = match ($operation) {
            'summarisation' => 'summarisation_allowed',
            'embedding', 'indexing' => 'embedding_indexing_allowed',
            'training', 'training_reuse' => 'training_reuse_allowed',
            'rag', 'retrieval' => 'rag_retrieval_allowed',
            'translation' => 'translation_allowed',
            'sensitivity_scan' => 'sensitivity_scan_allowed',
            default => 'ai_allowed',
        };

        return DB::table('ai_rights_restrictions')
            ->where($column, false)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->pluck('applies_to_iri')
            ->toArray();
    }

    public function getGlobalDefault(): ?object
    {
        return DB::table('ai_rights_restrictions')
            ->where('restriction_scope', 'global')
            ->first();
    }

    public function setGlobalDefault(array $data): void
    {
        $existing = $this->getGlobalDefault();
        $data['restriction_scope'] = 'global';
        $data['applies_to_iri'] = '*';
        $data['updated_at'] = now();

        if ($existing) {
            DB::table('ai_rights_restrictions')->where('id', $existing->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('ai_rights_restrictions')->insert($data);
        }
    }

    public function getOperationTypes(): array
    {
        return [
            'summarisation' => 'Summarisation',
            'embedding' => 'Embedding / Indexing',
            'training_reuse' => 'Training Reuse',
            'rag' => 'RAG Retrieval',
            'translation' => 'Translation',
            'sensitivity_scan' => 'Sensitivity Scan',
        ];
    }

    public function getScopeTypes(): array
    {
        return [
            'entity' => 'Entity-specific',
            'collection' => 'Collection-wide',
            'global' => 'Global default',
        ];
    }
}
