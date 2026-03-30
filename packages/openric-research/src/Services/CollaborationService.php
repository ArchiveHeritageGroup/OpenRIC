<?php

declare(strict_types=1);

namespace OpenRiC\Research\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * CollaborationService -- Workspace and Collaboration Management.
 *
 * Handles private workspaces, member management, resources, and discussions.
 * Workspaces are private by default to protect researcher data.
 *
 * Adapted from Heratio AhgResearch\Services\CollaborationService.
 * PostgreSQL ILIKE used for all text searches.
 */
class CollaborationService
{
    // =========================================================================
    // WORKSPACE MANAGEMENT
    // =========================================================================

    public function createWorkspace(int $ownerId, array $data): int
    {
        $workspaceId = DB::table('research_workspace')->insertGetId([
            'owner_id'    => $ownerId,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'visibility'  => $data['visibility'] ?? 'private',
            'share_token' => bin2hex(random_bytes(32)),
            'settings'    => isset($data['settings']) ? json_encode($data['settings']) : null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('research_workspace_member')->insert([
            'workspace_id'  => $workspaceId,
            'researcher_id' => $ownerId,
            'role'          => 'owner',
            'invited_by'    => $ownerId,
            'invited_at'    => now(),
            'accepted_at'   => now(),
            'status'        => 'accepted',
        ]);

        return $workspaceId;
    }

    public function getWorkspace(int $workspaceId): ?object
    {
        $workspace = DB::table('research_workspace as w')
            ->leftJoin('research_researcher as r', 'w.owner_id', '=', 'r.id')
            ->where('w.id', $workspaceId)
            ->select(
                'w.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email'
            )
            ->first();

        if ($workspace) {
            $workspace->members = $this->getMembers($workspaceId);
            $workspace->member_count = count(array_filter($workspace->members, fn ($m) => $m->status === 'accepted'));
            $workspace->resource_count = (int) DB::table('research_workspace_resource')
                ->where('workspace_id', $workspaceId)
                ->count();
            $workspace->discussion_count = (int) DB::table('research_discussion')
                ->where('workspace_id', $workspaceId)
                ->whereNull('parent_id')
                ->count();
        }

        return $workspace;
    }

    public function getWorkspaces(int $researcherId, array $filters = []): array
    {
        $query = DB::table('research_workspace as w')
            ->join('research_workspace_member as wm', function ($join) use ($researcherId) {
                $join->on('w.id', '=', 'wm.workspace_id')
                    ->where('wm.researcher_id', '=', $researcherId)
                    ->where('wm.status', '=', 'accepted');
            })
            ->leftJoin('research_researcher as r', 'w.owner_id', '=', 'r.id')
            ->select(
                'w.*',
                'wm.role as my_role',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name'
            );

        if (!empty($filters['visibility'])) {
            $query->where('w.visibility', $filters['visibility']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('w.name', 'ILIKE', $search)
                    ->orWhere('w.description', 'ILIKE', $search);
            });
        }
        if (!empty($filters['owned_only'])) {
            $query->where('w.owner_id', $researcherId);
        }

        $workspaces = $query->orderBy('w.updated_at', 'desc')->get()->toArray();

        foreach ($workspaces as &$workspace) {
            $workspace->member_count = (int) DB::table('research_workspace_member')
                ->where('workspace_id', $workspace->id)
                ->where('status', 'accepted')
                ->count();
            $workspace->resource_count = (int) DB::table('research_workspace_resource')
                ->where('workspace_id', $workspace->id)
                ->count();
        }

        return $workspaces;
    }

    public function updateWorkspace(int $workspaceId, array $data): bool
    {
        $allowed = ['name', 'description', 'visibility', 'settings'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        $updateData['updated_at'] = now();

        if (isset($updateData['settings']) && is_array($updateData['settings'])) {
            $updateData['settings'] = json_encode($updateData['settings']);
        }

        return DB::table('research_workspace')
            ->where('id', $workspaceId)
            ->update($updateData) >= 0;
    }

    public function deleteWorkspace(int $workspaceId): bool
    {
        DB::table('research_workspace_member')->where('workspace_id', $workspaceId)->delete();
        DB::table('research_workspace_resource')->where('workspace_id', $workspaceId)->delete();
        DB::table('research_discussion')->where('workspace_id', $workspaceId)->delete();

        return DB::table('research_workspace')->where('id', $workspaceId)->delete() > 0;
    }

    public function canAccess(int $workspaceId, int $researcherId, ?string $requiredRole = null): bool
    {
        $workspace = DB::table('research_workspace')->where('id', $workspaceId)->first();

        if (!$workspace) {
            return false;
        }

        if ($workspace->visibility === 'public' && ($requiredRole === null || $requiredRole === 'viewer')) {
            return true;
        }

        $member = DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->where('status', 'accepted')
            ->first();

        if (!$member) {
            return false;
        }
        if ($requiredRole === null) {
            return true;
        }

        $roleHierarchy = ['owner' => 4, 'admin' => 3, 'editor' => 2, 'viewer' => 1];
        $userLevel = $roleHierarchy[$member->role] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    // =========================================================================
    // MEMBER MANAGEMENT
    // =========================================================================

    public function addMember(int $workspaceId, int $researcherId, string $role, int $invitedBy): array
    {
        $existing = DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return ['success' => false, 'error' => 'Researcher is already a member'];
            }
            if ($existing->status === 'pending') {
                return ['success' => false, 'error' => 'Invitation already pending'];
            }
            DB::table('research_workspace_member')
                ->where('id', $existing->id)
                ->update([
                    'role'        => $role,
                    'invited_by'  => $invitedBy,
                    'invited_at'  => now(),
                    'accepted_at' => null,
                    'status'      => 'pending',
                ]);
            return ['success' => true, 'message' => 'Invitation sent'];
        }

        DB::table('research_workspace_member')->insert([
            'workspace_id'  => $workspaceId,
            'researcher_id' => $researcherId,
            'role'          => $role,
            'invited_by'    => $invitedBy,
            'invited_at'    => now(),
            'status'        => 'pending',
        ]);

        return ['success' => true, 'message' => 'Invitation sent'];
    }

    public function acceptMembership(int $workspaceId, int $researcherId): bool
    {
        return DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->where('status', 'pending')
            ->update([
                'accepted_at' => now(),
                'status'      => 'accepted',
            ]) > 0;
    }

    public function declineMembership(int $workspaceId, int $researcherId): bool
    {
        return DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->where('status', 'pending')
            ->update(['status' => 'declined']) > 0;
    }

    public function removeMember(int $workspaceId, int $researcherId): bool
    {
        $workspace = DB::table('research_workspace')->where('id', $workspaceId)->first();
        if ($workspace && (int) $workspace->owner_id === $researcherId) {
            return false;
        }

        return DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->update(['status' => 'removed']) > 0;
    }

    public function updateMemberRole(int $workspaceId, int $researcherId, string $newRole): bool
    {
        $member = DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->first();

        if (!$member || $member->role === 'owner') {
            return false;
        }

        return DB::table('research_workspace_member')
            ->where('workspace_id', $workspaceId)
            ->where('researcher_id', $researcherId)
            ->update(['role' => $newRole]) > 0;
    }

    public function getMembers(int $workspaceId, bool $acceptedOnly = false): array
    {
        $query = DB::table('research_workspace_member as wm')
            ->join('research_researcher as r', 'wm.researcher_id', '=', 'r.id')
            ->where('wm.workspace_id', $workspaceId)
            ->select(
                'wm.*',
                'r.first_name',
                'r.last_name',
                'r.email',
                'r.institution',
                'r.orcid_id'
            );

        if ($acceptedOnly) {
            $query->where('wm.status', 'accepted');
        }

        return $query->orderByRaw("CASE wm.role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'editor' THEN 3 WHEN 'viewer' THEN 4 ELSE 5 END")
            ->get()
            ->toArray();
    }

    public function getPendingInvitations(int $researcherId): array
    {
        return DB::table('research_workspace_member as wm')
            ->join('research_workspace as w', 'wm.workspace_id', '=', 'w.id')
            ->join('research_researcher as inviter', 'wm.invited_by', '=', 'inviter.id')
            ->where('wm.researcher_id', $researcherId)
            ->where('wm.status', 'pending')
            ->select(
                'wm.*',
                'w.name as workspace_name',
                'w.description as workspace_description',
                'inviter.first_name as inviter_first_name',
                'inviter.last_name as inviter_last_name'
            )
            ->orderBy('wm.invited_at', 'desc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // RESOURCES
    // =========================================================================

    public function addResource(int $workspaceId, array $data, int $addedBy): int
    {
        $maxOrder = (int) (DB::table('research_workspace_resource')
            ->where('workspace_id', $workspaceId)
            ->max('sort_order') ?? 0);

        return DB::table('research_workspace_resource')->insertGetId([
            'workspace_id'  => $workspaceId,
            'resource_type' => $data['resource_type'],
            'resource_id'   => $data['resource_id'] ?? null,
            'external_url'  => $data['external_url'] ?? null,
            'title'         => $data['title'] ?? null,
            'description'   => $data['description'] ?? null,
            'added_by'      => $addedBy,
            'sort_order'    => $maxOrder + 1,
            'added_at'      => now(),
        ]);
    }

    public function removeResource(int $resourceId): bool
    {
        return DB::table('research_workspace_resource')
            ->where('id', $resourceId)
            ->delete() > 0;
    }

    public function getResources(int $workspaceId, ?string $type = null): array
    {
        $query = DB::table('research_workspace_resource as wr')
            ->leftJoin('research_researcher as r', 'wr.added_by', '=', 'r.id')
            ->where('wr.workspace_id', $workspaceId)
            ->select(
                'wr.*',
                'r.first_name as added_by_first_name',
                'r.last_name as added_by_last_name'
            );

        if ($type) {
            $query->where('wr.resource_type', $type);
        }

        $resources = $query->orderBy('wr.sort_order')->get()->toArray();

        foreach ($resources as &$resource) {
            if ($resource->resource_id) {
                $resource->linked_resource = match ($resource->resource_type) {
                    'collection'   => DB::table('research_collection')->where('id', $resource->resource_id)->first(),
                    'project'      => DB::table('research_project')->where('id', $resource->resource_id)->first(),
                    'bibliography' => DB::table('research_bibliography')->where('id', $resource->resource_id)->first(),
                    'saved_search' => DB::table('research_saved_search')->where('id', $resource->resource_id)->first(),
                    default        => null,
                };
            }
        }

        return $resources;
    }

    // =========================================================================
    // DISCUSSIONS
    // =========================================================================

    public function createDiscussion(array $data): int
    {
        return DB::table('research_discussion')->insertGetId([
            'workspace_id'  => $data['workspace_id'] ?? null,
            'project_id'    => $data['project_id'] ?? null,
            'parent_id'     => $data['parent_id'] ?? null,
            'researcher_id' => $data['researcher_id'],
            'subject'       => $data['subject'] ?? null,
            'content'       => $data['content'],
            'is_pinned'     => $data['is_pinned'] ?? false,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function getDiscussions(int $workspaceId, bool $topLevelOnly = true): array
    {
        $query = DB::table('research_discussion as d')
            ->join('research_researcher as r', 'd.researcher_id', '=', 'r.id')
            ->where('d.workspace_id', $workspaceId)
            ->select('d.*', 'r.first_name', 'r.last_name', 'r.email');

        if ($topLevelOnly) {
            $query->whereNull('d.parent_id');
        }

        $discussions = $query->orderByDesc('d.is_pinned')
            ->orderBy('d.created_at', 'desc')
            ->get()
            ->toArray();

        if ($topLevelOnly) {
            foreach ($discussions as &$discussion) {
                $discussion->reply_count = (int) DB::table('research_discussion')
                    ->where('parent_id', $discussion->id)
                    ->count();
            }
        }

        return $discussions;
    }

    public function getProjectDiscussions(int $projectId, bool $topLevelOnly = true): array
    {
        $query = DB::table('research_discussion as d')
            ->join('research_researcher as r', 'd.researcher_id', '=', 'r.id')
            ->where('d.project_id', $projectId)
            ->select('d.*', 'r.first_name', 'r.last_name');

        if ($topLevelOnly) {
            $query->whereNull('d.parent_id');
        }

        $discussions = $query->orderByDesc('d.is_pinned')
            ->orderBy('d.created_at', 'desc')
            ->get()
            ->toArray();

        if ($topLevelOnly) {
            foreach ($discussions as &$discussion) {
                $discussion->reply_count = (int) DB::table('research_discussion')
                    ->where('parent_id', $discussion->id)
                    ->count();
            }
        }

        return $discussions;
    }

    public function getDiscussion(int $discussionId): ?object
    {
        $discussion = DB::table('research_discussion as d')
            ->join('research_researcher as r', 'd.researcher_id', '=', 'r.id')
            ->where('d.id', $discussionId)
            ->select('d.*', 'r.first_name', 'r.last_name', 'r.email')
            ->first();

        if ($discussion) {
            $discussion->replies = DB::table('research_discussion as d')
                ->join('research_researcher as r', 'd.researcher_id', '=', 'r.id')
                ->where('d.parent_id', $discussionId)
                ->select('d.*', 'r.first_name', 'r.last_name')
                ->orderBy('d.created_at')
                ->get()
                ->toArray();
        }

        return $discussion;
    }

    public function addReply(int $parentId, int $researcherId, string $content): int
    {
        $parent = DB::table('research_discussion')
            ->where('id', $parentId)
            ->first();

        if (!$parent) {
            throw new RuntimeException('Parent discussion not found');
        }

        return DB::table('research_discussion')->insertGetId([
            'workspace_id'  => $parent->workspace_id,
            'project_id'    => $parent->project_id,
            'parent_id'     => $parentId,
            'researcher_id' => $researcherId,
            'content'       => $content,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function deleteDiscussion(int $discussionId): bool
    {
        DB::table('research_discussion')->where('parent_id', $discussionId)->delete();

        return DB::table('research_discussion')
            ->where('id', $discussionId)
            ->delete() > 0;
    }

    public function updateDiscussion(int $discussionId, array $data): bool
    {
        $allowed = ['subject', 'content', 'is_pinned', 'is_resolved', 'resolved_by', 'resolved_at'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        $updateData['updated_at'] = now();

        return DB::table('research_discussion')
            ->where('id', $discussionId)
            ->update($updateData) >= 0;
    }

    public function resolveDiscussion(int $discussionId, int $resolvedBy): bool
    {
        return DB::table('research_discussion')
            ->where('id', $discussionId)
            ->update([
                'is_resolved' => true,
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
                'updated_at'  => now(),
            ]) > 0;
    }

    public function pinDiscussion(int $discussionId, bool $pinned): bool
    {
        return DB::table('research_discussion')
            ->where('id', $discussionId)
            ->update([
                'is_pinned'  => $pinned,
                'updated_at' => now(),
            ]) > 0;
    }
}
