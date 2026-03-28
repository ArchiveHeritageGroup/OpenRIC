<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Audit\Contracts\AuditServiceInterface;

class AuditService implements AuditServiceInterface
{
    public function log(string $action, array $data): void
    {
        $user = Auth::user();

        DB::table('audit_log')->insert(array_merge([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user?->id,
            'username' => $user?->username,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'action' => $action,
            'created_at' => now(),
        ], $data));
    }

    public function logCreate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $newValues = null): void
    {
        $this->log('create', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $entityTitle,
            'new_values' => $newValues !== null ? json_encode($newValues) : null,
        ]);
    }

    public function logUpdate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $changedFields = null;
        if ($oldValues !== null && $newValues !== null) {
            $changedFields = array_keys(array_diff_assoc($newValues, $oldValues));
        }

        $this->log('update', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $entityTitle,
            'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values' => $newValues !== null ? json_encode($newValues) : null,
            'changed_fields' => $changedFields !== null ? json_encode($changedFields) : null,
        ]);
    }

    public function logDelete(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null): void
    {
        $this->log('delete', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $entityTitle,
            'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
        ]);
    }

    public function logAuth(string $action, ?string $description = null): void
    {
        $this->log($action, [
            'module' => 'auth',
            'description' => $description,
        ]);
    }

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('audit_log')->orderByDesc('created_at');

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $items = $query->limit($limit)->offset($offset)->get()->toArray();

        return ['items' => $items, 'total' => $total];
    }

    public function find(int $id): ?array
    {
        $record = DB::table('audit_log')->where('id', $id)->first();

        if ($record === null) {
            return null;
        }

        return (array) $record;
    }
}
