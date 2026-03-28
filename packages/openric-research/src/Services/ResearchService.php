<?php

declare(strict_types=1);

namespace OpenRiC\Research\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Research\Contracts\ResearchServiceInterface;

/**
 * ResearchService — workspace, annotation, citation, assessment CRUD.
 *
 * Adapted from Heratio ahg-research ResearchService (608 lines).
 * Uses PostgreSQL tables: research_workspaces, research_workspace_items,
 * research_annotations, research_citations, research_assessments.
 */
class ResearchService implements ResearchServiceInterface
{
    // =========================================================================
    // WORKSPACES
    // =========================================================================

    public function createWorkspace(array $data): int
    {
        $workspaceId = DB::table('research_workspaces')->insertGetId([
            'user_id'     => $data['user_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_public'   => $data['is_public'] ?? false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->logAudit('create', 'ResearchWorkspace', $workspaceId, [], $data);

        return $workspaceId;
    }

    public function getWorkspaces(int $userId): array
    {
        return DB::table('research_workspaces')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getWorkspace(int $workspaceId): ?object
    {
        $workspace = DB::table('research_workspaces')
            ->where('id', $workspaceId)
            ->first();

        if ($workspace) {
            $workspace->items = DB::table('research_workspace_items')
                ->where('workspace_id', $workspaceId)
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()
                ->toArray();
        }

        return $workspace;
    }

    public function addItemToWorkspace(int $workspaceId, array $data): int
    {
        $maxSort = (int) DB::table('research_workspace_items')
            ->where('workspace_id', $workspaceId)
            ->max('sort_order');

        $itemId = DB::table('research_workspace_items')->insertGetId([
            'workspace_id' => $workspaceId,
            'entity_iri'   => $data['entity_iri'],
            'entity_type'  => $data['entity_type'],
            'title'        => $data['title'],
            'sort_order'   => $data['sort_order'] ?? ($maxSort + 1),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->logAudit('add_item', 'ResearchWorkspaceItem', $itemId, [], $data);

        return $itemId;
    }

    public function removeItemFromWorkspace(int $workspaceId, string $entityIri): bool
    {
        $item = DB::table('research_workspace_items')
            ->where('workspace_id', $workspaceId)
            ->where('entity_iri', $entityIri)
            ->first();

        if (!$item) {
            return false;
        }

        $deleted = DB::table('research_workspace_items')
            ->where('workspace_id', $workspaceId)
            ->where('entity_iri', $entityIri)
            ->delete() > 0;

        if ($deleted) {
            $this->logAudit('remove_item', 'ResearchWorkspaceItem', (int) $item->id, (array) $item, []);
        }

        return $deleted;
    }

    // =========================================================================
    // ANNOTATIONS
    // =========================================================================

    public function createAnnotation(array $data): int
    {
        $sanitizedContent = $this->sanitizeHtml($data['content']);

        $annotationId = DB::table('research_annotations')->insertGetId([
            'user_id'         => $data['user_id'],
            'entity_iri'      => $data['entity_iri'],
            'annotation_type' => $data['annotation_type'],
            'content'         => $sanitizedContent,
            'is_public'       => $data['is_public'] ?? false,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->logAudit('create', 'ResearchAnnotation', $annotationId, [], $data);

        return $annotationId;
    }

    public function getAnnotationsForEntity(string $entityIri, ?int $userId = null): array
    {
        $query = DB::table('research_annotations as ra')
            ->leftJoin('users', 'ra.user_id', '=', 'users.id')
            ->where('ra.entity_iri', $entityIri)
            ->select('ra.*', 'users.name as user_name');

        if ($userId !== null) {
            // Show public annotations plus the user's own
            $query->where(function ($q) use ($userId) {
                $q->where('ra.is_public', true)
                  ->orWhere('ra.user_id', $userId);
            });
        } else {
            $query->where('ra.is_public', true);
        }

        return $query->orderByDesc('ra.created_at')->get()->toArray();
    }

    public function deleteAnnotation(int $annotationId, int $userId): bool
    {
        $annotation = DB::table('research_annotations')
            ->where('id', $annotationId)
            ->where('user_id', $userId)
            ->first();

        if (!$annotation) {
            return false;
        }

        $deleted = DB::table('research_annotations')
            ->where('id', $annotationId)
            ->where('user_id', $userId)
            ->delete() > 0;

        if ($deleted) {
            $this->logAudit('delete', 'ResearchAnnotation', $annotationId, (array) $annotation, []);
        }

        return $deleted;
    }

    public function searchAnnotations(int $userId, string $query): array
    {
        $pattern = '%' . $query . '%';

        return DB::table('research_annotations')
            ->where('user_id', $userId)
            ->where(function ($q) use ($pattern) {
                $q->where('content', 'ILIKE', $pattern)
                  ->orWhere('annotation_type', 'ILIKE', $pattern);
            })
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // CITATIONS
    // =========================================================================

    public function getCitations(string $entityIri, ?int $userId = null): array
    {
        $query = DB::table('research_citations as rc')
            ->leftJoin('users', 'rc.user_id', '=', 'users.id')
            ->where('rc.entity_iri', $entityIri)
            ->select('rc.*', 'users.name as user_name');

        if ($userId !== null) {
            $query->where('rc.user_id', $userId);
        }

        return $query->orderByDesc('rc.created_at')->get()->toArray();
    }

    public function addCitation(array $data): int
    {
        $citationId = DB::table('research_citations')->insertGetId([
            'user_id'        => $data['user_id'],
            'entity_iri'     => $data['entity_iri'],
            'citation_style' => $data['citation_style'],
            'citation_text'  => $data['citation_text'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->logAudit('create', 'ResearchCitation', $citationId, [], $data);

        return $citationId;
    }

    public function deleteCitation(int $citationId, int $userId): bool
    {
        return DB::table('research_citations')
            ->where('id', $citationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Generate a citation in the specified style for an entity.
     *
     * Adapted from Heratio ResearchService::generateCitation.
     *
     * @param  string $entityIri   IRI of the entity
     * @param  string $title       entity title
     * @param  string $repository  holding repository name
     * @param  string $identifier  entity identifier/reference code
     * @param  string $style       citation style: chicago, mla, apa, harvard, turabian, unisa
     * @return array{citation: string, style: string}
     */
    public function generateCitation(string $entityIri, string $title, string $repository, string $identifier, string $style): array
    {
        $accessDate = date('j F Y');
        $url = config('app.url') . '/entities/' . urlencode($entityIri);

        return match ($style) {
            'chicago'  => ['citation' => "{$title}. {$identifier}. {$repository}. Accessed {$accessDate}. {$url}.", 'style' => 'Chicago'],
            'mla'      => ['citation' => "\"{$title}.\" {$repository}, {$identifier}. Web. {$accessDate}. <{$url}>.", 'style' => 'MLA'],
            'turabian' => ['citation' => "{$title}. {$identifier}. {$repository}. {$url}.", 'style' => 'Turabian'],
            'apa'      => ['citation' => "{$repository}. ({$accessDate}). {$title} [{$identifier}]. Retrieved from {$url}", 'style' => 'APA'],
            'harvard'  => ['citation' => "{$repository} ({$accessDate}) {$title} [{$identifier}]. Available at: {$url} (Accessed: {$accessDate}).", 'style' => 'Harvard'],
            'unisa'    => ['citation' => "{$repository}. {$title}. {$identifier}. [Online]. Available: {$url} [{$accessDate}].", 'style' => 'UNISA'],
            default    => ['citation' => "{$title}. {$identifier}. {$repository}. {$url}.", 'style' => $style],
        };
    }

    // =========================================================================
    // ASSESSMENTS
    // =========================================================================

    public function getAssessments(string $entityIri): array
    {
        return DB::table('research_assessments as ra')
            ->leftJoin('users', 'ra.user_id', '=', 'users.id')
            ->where('ra.entity_iri', $entityIri)
            ->select('ra.*', 'users.name as user_name')
            ->orderByDesc('ra.created_at')
            ->get()
            ->toArray();
    }

    public function addAssessment(array $data): int
    {
        $assessmentId = DB::table('research_assessments')->insertGetId([
            'user_id'         => $data['user_id'],
            'entity_iri'      => $data['entity_iri'],
            'assessment_type' => $data['assessment_type'],
            'content'         => $this->sanitizeHtml($data['content']),
            'score'           => $data['score'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->logAudit('create', 'ResearchAssessment', $assessmentId, [], $data);

        return $assessmentId;
    }

    // =========================================================================
    // DASHBOARD STATS
    // =========================================================================

    public function getDashboardStats(int $userId): array
    {
        return [
            'workspace_count'  => (int) DB::table('research_workspaces')->where('user_id', $userId)->count(),
            'annotation_count' => (int) DB::table('research_annotations')->where('user_id', $userId)->count(),
            'citation_count'   => (int) DB::table('research_citations')->where('user_id', $userId)->count(),
            'assessment_count' => (int) DB::table('research_assessments')->where('user_id', $userId)->count(),
        ];
    }

    // =========================================================================
    // HTML SANITIZATION (from Heratio ResearchService)
    // =========================================================================

    protected function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote><code><pre><hr><table><thead><tbody><tr><th><td><span><div><sub><sup>';
        $html = strip_tags($html, $allowed);
        $html = (string) preg_replace('/\bon\w+\s*=/i', 'data-removed=', $html);
        $html = (string) preg_replace('/javascript\s*:/i', '', $html);

        return $html;
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    protected function logAudit(string $action, string $objectType, int $objectId, array $oldValues, array $newValues): void
    {
        try {
            DB::table('audit_log')->insert([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'entity_type' => $objectType,
                'entity_id'   => (string) $objectId,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            // Audit logging is non-critical; do not break the application
        }
    }
}
