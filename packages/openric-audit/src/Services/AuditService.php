<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Audit\Contracts\AuditServiceInterface;

/**
 * Adapted from: /usr/share/nginx/heratio/packages/ahg-audit-trail/src/Controllers/AuditTrailController.php (489 lines)
 * Heratio has logic in the controller — OpenRiC extracts it into a proper service layer.
 */
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
        $query = DB::table('audit_log');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (! empty($filters['user'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('username', 'ILIKE', '%' . $filters['user'] . '%')
                    ->orWhere('user_email', 'ILIKE', '%' . $filters['user'] . '%');
            });
        }
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $total = $query->count();

        $items = $query->select([
            'id', 'uuid', 'user_id', 'username', 'user_email', 'ip_address', 'user_agent',
            'session_id', 'action', 'entity_type', 'entity_id', 'entity_title', 'module',
            'action_name', 'old_values', 'new_values', 'changed_fields',
            'security_classification', 'description', 'created_at',
        ])
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

        $entityTypes = DB::table('audit_log')
            ->select('entity_type')
            ->distinct()
            ->whereNotNull('entity_type')
            ->orderBy('entity_type')
            ->pluck('entity_type')
            ->toArray();

        $actions = DB::table('audit_log')
            ->select('action')
            ->distinct()
            ->whereNotNull('action')
            ->orderBy('action')
            ->pluck('action')
            ->toArray();

        return ['items' => $items, 'total' => $total, 'entityTypes' => $entityTypes, 'actions' => $actions];
    }

    public function find(int $id): ?array
    {
        $entry = DB::table('audit_log')
            ->where('id', $id)
            ->select([
                'id', 'uuid', 'user_id', 'username', 'user_email', 'ip_address', 'user_agent',
                'session_id', 'action', 'entity_type', 'entity_id', 'entity_title', 'module',
                'action_name', 'old_values', 'new_values', 'changed_fields',
                'security_classification', 'description', 'created_at',
            ])
            ->first();

        return $entry ? (array) $entry : null;
    }

    /**
     * Statistics dashboard — adapted from Heratio AuditTrailController::statistics()
     */
    public function getStatistics(int $days = 30): array
    {
        $fromDate = Carbon::now()->subDays($days)->startOfDay();
        $baseQuery = DB::table('audit_log')->where('created_at', '>=', $fromDate);

        $totalActions = (clone $baseQuery)->count();
        $createdCount = (clone $baseQuery)->where('action', 'create')->count();
        $updatedCount = (clone $baseQuery)->where('action', 'update')->count();
        $deletedCount = (clone $baseQuery)->where('action', 'delete')->count();

        $mostActiveUsers = (clone $baseQuery)
            ->select('username', DB::raw('COUNT(*) as action_count'))
            ->whereNotNull('username')
            ->groupBy('username')
            ->orderByDesc('action_count')
            ->limit(10)
            ->get()
            ->toArray();

        $recentFailed = DB::table('audit_log')
            ->where('created_at', '>=', $fromDate)
            ->where('action', 'failed')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();

        return compact('totalActions', 'createdCount', 'updatedCount', 'deletedCount', 'mostActiveUsers', 'recentFailed');
    }

    /**
     * Get entity history by IRI — adapted from Heratio entityHistory/entityHistoryByType
     */
    public function getEntityHistory(string $entityId, ?string $entityType = null, int $limit = 200): Collection
    {
        $query = DB::table('audit_log')->where('entity_id', $entityId);

        if ($entityType !== null) {
            $query->where('entity_type', $entityType);
        }

        return $query->orderByDesc('created_at')->limit($limit)->get();
    }

    /**
     * Get user activity — adapted from Heratio userActivityById
     */
    public function getUserActivity(int $userId, int $limit = 200): array
    {
        $rows = DB::table('audit_log')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();

        $username = DB::table('users')->where('id', $userId)->value('username') ?? "User #{$userId}";

        return ['rows' => $rows, 'username' => $username];
    }

    /**
     * Compare old/new values — adapted from Heratio compareData
     */
    public function compareData(int $id): ?array
    {
        $entry = DB::table('audit_log')->where('id', $id)
            ->select(['id', 'entity_type', 'entity_id', 'entity_title', 'action', 'old_values', 'new_values', 'changed_fields', 'username', 'created_at'])
            ->first();

        if ($entry === null) {
            return null;
        }

        $result = (array) $entry;
        $result['old_values_decoded'] = $entry->old_values ? json_decode($entry->old_values, true) : [];
        $result['new_values_decoded'] = $entry->new_values ? json_decode($entry->new_values, true) : [];
        $result['changed_fields_decoded'] = $entry->changed_fields ? json_decode($entry->changed_fields, true) : [];

        return $result;
    }

    /**
     * Export audit log — adapted from Heratio export
     */
    public function export(array $filters = [], int $limit = 10000): Collection
    {
        $query = DB::table('audit_log');

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->select('created_at', 'username', 'action', 'entity_type', 'entity_id', 'entity_title', 'ip_address')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Audit settings — adapted from Heratio settings method
     * Uses OpenRiC settings table instead of ahg_settings
     */
    public function getSettings(): array
    {
        $settingKeys = [
            'audit_enabled', 'audit_views', 'audit_searches', 'audit_downloads',
            'audit_api_requests', 'audit_authentication', 'audit_sensitive_access',
            'audit_mask_sensitive', 'audit_ip_anonymize',
        ];

        $rows = DB::table('settings')
            ->where('group', 'audit')
            ->whereIn('key', $settingKeys)
            ->pluck('value', 'key');

        $settings = [];
        foreach ($settingKeys as $key) {
            $settings[$key] = $rows[$key] ?? '0';
        }

        return $settings;
    }

    public function saveSettings(array $data): void
    {
        $settingKeys = [
            'audit_enabled', 'audit_views', 'audit_searches', 'audit_downloads',
            'audit_api_requests', 'audit_authentication', 'audit_sensitive_access',
            'audit_mask_sensitive', 'audit_ip_anonymize',
        ];

        foreach ($settingKeys as $key) {
            $value = isset($data[$key]) ? '1' : '0';

            DB::table('settings')->updateOrInsert(
                ['group' => 'audit', 'key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
    }
}
