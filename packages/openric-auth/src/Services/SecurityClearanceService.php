<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Auth\Contracts\SecurityClearanceServiceInterface;
use OpenRiC\Auth\Models\SecurityClassification;

/**
 * Adapted from: /usr/share/nginx/heratio/packages/ahg-security-clearance/src/Services/SecurityClearanceService.php (867 lines)
 * Changes: OpenRiC\ namespace, PostgreSQL, object_iri instead of object_id, users table instead of user.
 */
class SecurityClearanceService implements SecurityClearanceServiceInterface
{
    private static array $classificationCache = [];

    // === Classification Level Management ===

    public function getClassificationLevels(): Collection
    {
        return DB::table('security_classifications')->where('active', true)->orderBy('level')->get();
    }

    public function getClassification(int $id): ?object
    {
        return DB::table('security_classifications')->where('id', $id)->first();
    }

    public static function getAllClassifications(): array
    {
        if (empty(self::$classificationCache)) {
            self::$classificationCache = DB::table('security_classifications')
                ->where('active', true)->orderBy('level')->get()->toArray();
        }
        return self::$classificationCache;
    }

    // === User Clearance Management ===

    public function getUserClearance(int $userId): ?SecurityClassification
    {
        $row = DB::table('user_security_clearance as usc')
            ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->where('usc.user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('usc.expires_at')->orWhere('usc.expires_at', '>=', now());
            })
            ->select('usc.*', 'sc.code', 'sc.name', 'sc.name as classification_name', 'sc.level', 'sc.color',
                'sc.requires_2fa', 'sc.watermark_required', 'sc.download_allowed', 'sc.print_allowed', 'sc.copy_allowed')
            ->first();

        return $row ? SecurityClassification::find($row->classification_id) : null;
    }

    public function getUserClearanceRecord(int $userId): ?object
    {
        return DB::table('user_security_clearance as usc')
            ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->leftJoin('users as granter', 'usc.granted_by', '=', 'granter.id')
            ->where('usc.user_id', $userId)
            ->select('usc.*', 'sc.code', 'sc.name as classification_name', 'sc.code as classification_code',
                'sc.level as classification_level', 'sc.color', 'granter.username as granted_by_name')
            ->first();
    }

    public function getUserClearanceLevel(int $userId): int
    {
        $clearance = $this->getUserClearance($userId);
        return $clearance ? $clearance->level : 0;
    }

    public function getAllUsersWithClearances(): array
    {
        return DB::table('users as u')
            ->leftJoin('user_security_clearance as usc', 'u.id', '=', 'usc.user_id')
            ->leftJoin('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->leftJoin('users as granter', 'usc.granted_by', '=', 'granter.id')
            ->select('u.id', 'u.username', 'u.email', 'u.active', 'usc.id as clearance_id',
                'usc.classification_id', 'usc.granted_at', 'usc.expires_at', 'usc.notes',
                'sc.name as classification_name', 'sc.code as classification_code',
                'sc.level as classification_level', 'sc.color', 'granter.username as granted_by_name')
            ->orderBy('u.username')->get()->toArray();
    }

    public function grantClearance(int $userId, int $classificationId, int $grantedBy, ?string $expiresAt = null, ?string $notes = null): bool
    {
        try {
            DB::beginTransaction();
            $previous = DB::table('user_security_clearance')->where('user_id', $userId)->first();
            $data = ['user_id' => $userId, 'classification_id' => $classificationId, 'granted_by' => $grantedBy,
                'granted_at' => now(), 'expires_at' => $expiresAt ?: null, 'notes' => $notes, 'updated_at' => now()];

            if ($previous) {
                $this->logClearanceChange($userId, 'updated', $previous->classification_id, $classificationId, $grantedBy, $notes);
                DB::table('user_security_clearance')->where('user_id', $userId)->update($data);
            } else {
                $data['created_at'] = now();
                $this->logClearanceChange($userId, 'granted', null, $classificationId, $grantedBy, $notes);
                DB::table('user_security_clearance')->insert($data);
            }
            DB::commit();
            return true;
        } catch (\Exception) { DB::rollBack(); return false; }
    }

    public function revokeClearance(int $userId, int $revokedBy, ?string $notes = null): bool
    {
        try {
            DB::beginTransaction();
            $previous = DB::table('user_security_clearance')->where('user_id', $userId)->first();
            if ($previous) {
                $this->logClearanceChange($userId, 'revoked', $previous->classification_id, null, $revokedBy, $notes ?: 'Clearance revoked');
                DB::table('user_security_clearance')->where('user_id', $userId)->delete();
            }
            DB::commit();
            return true;
        } catch (\Exception) { DB::rollBack(); return false; }
    }

    public function bulkGrant(array $userIds, int $classificationId, int $grantedBy, ?string $notes = null): int
    {
        $count = 0;
        foreach ($userIds as $uid) { if ($this->grantClearance((int) $uid, $classificationId, $grantedBy, null, $notes)) $count++; }
        return $count;
    }

    public function getClearanceHistory(int $userId): array
    {
        return DB::table('user_security_clearance_log as log')
            ->leftJoin('security_classifications as sc', 'log.classification_id', '=', 'sc.id')
            ->leftJoin('security_classifications as prev_sc', 'log.previous_classification_id', '=', 'prev_sc.id')
            ->leftJoin('users as actor', 'log.changed_by', '=', 'actor.id')
            ->where('log.user_id', $userId)
            ->select('log.*', 'sc.name as classification_name', 'sc.code as classification_code',
                'prev_sc.name as previous_name', 'actor.username as changed_by_name')
            ->orderByDesc('log.created_at')->get()->toArray();
    }

    private function logClearanceChange(int $userId, string $action, ?int $prevId, ?int $newId, int $changedBy, ?string $notes): void
    {
        DB::table('user_security_clearance_log')->insert([
            'user_id' => $userId, 'action' => $action, 'previous_classification_id' => $prevId,
            'classification_id' => $newId, 'changed_by' => $changedBy, 'notes' => $notes, 'created_at' => now(),
        ]);
    }

    // === Object Classification (IRI-based for RiC-O) ===

    public function getObjectClassification(string $objectIri): ?SecurityClassification
    {
        $row = DB::table('object_security_classification as osc')
            ->join('security_classifications as sc', 'osc.classification_id', '=', 'sc.id')
            ->where('osc.object_iri', $objectIri)->where('osc.active', true)
            ->select('osc.*', 'sc.code', 'sc.name', 'sc.level', 'sc.color', 'sc.requires_2fa', 'sc.watermark_required')
            ->first();
        return $row ? SecurityClassification::find($row->classification_id) : null;
    }

    public function classifyObject(string $objectIri, int $classificationId, int $userId, ?string $reason = null, ?array $compartmentIds = null): bool
    {
        try {
            DB::beginTransaction();
            DB::table('object_security_classification')->where('object_iri', $objectIri)->update(['active' => false]);
            DB::table('object_security_classification')->insert([
                'object_iri' => $objectIri, 'classification_id' => $classificationId, 'classified_by' => $userId,
                'classified_at' => now(), 'reason' => $reason, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($compartmentIds) {
                DB::table('object_compartment_access')->where('object_iri', $objectIri)->delete();
                foreach ($compartmentIds as $cid) {
                    DB::table('object_compartment_access')->insert([
                        'object_iri' => $objectIri, 'compartment_id' => (int) $cid,
                        'granted_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            $this->logSecurityAudit($userId, $objectIri, 'classify', ['classification_id' => $classificationId, 'reason' => $reason]);
            DB::commit();
            return true;
        } catch (\Exception) { DB::rollBack(); return false; }
    }

    public function declassifyObject(string $objectIri, int $userId, ?int $newClassificationId = null, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();
            $current = $this->getObjectClassification($objectIri);
            DB::table('object_security_classification')->where('object_iri', $objectIri)->update(['active' => false]);
            if ($newClassificationId) {
                DB::table('object_security_classification')->insert([
                    'object_iri' => $objectIri, 'classification_id' => $newClassificationId, 'classified_by' => $userId,
                    'classified_at' => now(), 'reason' => $reason ?? 'Declassified', 'active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $this->logSecurityAudit($userId, $objectIri, 'declassify', [
                'previous_classification_id' => $current->id ?? null, 'new_classification_id' => $newClassificationId, 'reason' => $reason,
            ]);
            DB::commit();
            return true;
        } catch (\Exception) { DB::rollBack(); return false; }
    }

    public function canAccessObject(int $userId, string $objectIri): bool
    {
        $objClass = $this->getObjectClassification($objectIri);
        if ($objClass === null) return true;
        $userLevel = $this->getUserClearanceLevel($userId);
        if ($userLevel < $objClass->level) return false;

        $objComps = DB::table('object_compartment_access')->where('object_iri', $objectIri)->pluck('compartment_id')->toArray();
        if (empty($objComps)) return true;
        $userComps = DB::table('user_compartment_access')->where('user_id', $userId)->pluck('compartment_id')->toArray();
        return empty(array_diff($objComps, $userComps));
    }

    // === Compartments ===

    public function getCompartments(): Collection { return DB::table('security_compartments')->orderBy('name')->get(); }

    public function getCompartmentUserCounts(): array
    {
        return DB::table('user_compartment_access')->select('compartment_id', DB::raw('COUNT(*) as count'))
            ->groupBy('compartment_id')->pluck('count', 'compartment_id')->toArray();
    }

    public function getCompartmentAccessGrants(): Collection
    {
        return DB::table('user_compartment_access as uca')
            ->join('security_compartments as sc', 'uca.compartment_id', '=', 'sc.id')
            ->join('users as u', 'uca.user_id', '=', 'u.id')
            ->leftJoin('users as granter', 'uca.granted_by', '=', 'granter.id')
            ->select('uca.*', 'sc.name as compartment_name', 'sc.code as compartment_code',
                'u.username', 'granter.username as granted_by_name')
            ->orderBy('sc.name')->orderBy('u.username')->get();
    }

    public function grantCompartmentAccess(int $userId, int $compartmentId, int $grantedBy): bool
    {
        try {
            DB::table('user_compartment_access')->updateOrInsert(
                ['user_id' => $userId, 'compartment_id' => $compartmentId],
                ['granted_by' => $grantedBy, 'created_at' => now(), 'updated_at' => now()]
            );
            return true;
        } catch (\Exception) { return false; }
    }

    public function revokeCompartmentAccess(int $userId, int $compartmentId): bool
    {
        return DB::table('user_compartment_access')->where('user_id', $userId)->where('compartment_id', $compartmentId)->delete() > 0;
    }

    // === Access Requests (IRI-based) ===

    public function getAccessRequests(?string $status = 'pending'): Collection
    {
        $q = DB::table('security_access_requests as sar')->join('users as u', 'sar.user_id', '=', 'u.id')->select('sar.*', 'u.username');
        if ($status) $q->where('sar.status', $status);
        return $q->orderByDesc('sar.created_at')->get();
    }

    public function submitAccessRequest(int $userId, string $objectIri, string $requestType, string $justification, string $priority = 'normal', ?int $durationHours = 24): bool
    {
        try {
            DB::table('security_access_requests')->insert([
                'user_id' => $userId, 'object_iri' => $objectIri, 'request_type' => $requestType,
                'justification' => $justification, 'priority' => $priority, 'duration_hours' => $durationHours,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception) { return false; }
    }

    public function reviewAccessRequest(int $requestId, string $decision, int $reviewerId, ?string $notes = null): bool
    {
        try {
            DB::table('security_access_requests')->where('id', $requestId)->update([
                'status' => $decision, 'reviewed_by' => $reviewerId, 'reviewed_at' => now(),
                'review_notes' => $notes, 'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception) { return false; }
    }

    // === Dashboard Statistics ===

    public function getDashboardStatistics(): array
    {
        $stats = ['pending_requests' => 0, 'expiring_clearances' => 0, 'recent_denials' => 0, 'clearances_by_level' => [], 'objects_by_level' => []];
        try {
            $stats['pending_requests'] = DB::table('security_access_requests')->where('status', 'pending')->count();
            $stats['expiring_clearances'] = DB::table('user_security_clearance')->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(30))->where('expires_at', '>', now())->count();
            $stats['recent_denials'] = DB::table('security_access_requests')->where('status', 'denied')
                ->where('updated_at', '>=', now()->subDays(7))->count();
            $stats['clearances_by_level'] = DB::table('user_security_clearance as usc')
                ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
                ->select('sc.name', 'sc.color', DB::raw('COUNT(*) as count'))
                ->groupBy('sc.id', 'sc.name', 'sc.color')->orderBy('sc.level')->get()->toArray();
            $stats['objects_by_level'] = DB::table('object_security_classification as osc')
                ->join('security_classifications as sc', 'osc.classification_id', '=', 'sc.id')
                ->where('osc.active', true)->select('sc.name', 'sc.color', DB::raw('COUNT(*) as count'))
                ->groupBy('sc.id', 'sc.name', 'sc.color')->orderBy('sc.level')->get()->toArray();
        } catch (\Exception) {}
        return $stats;
    }

    public function getPendingRequests(int $limit = 10): array
    {
        return DB::table('security_access_requests as sar')->join('users as u', 'sar.user_id', '=', 'u.id')
            ->where('sar.status', 'pending')->select('sar.*', 'u.username', 'sar.id as request_id')
            ->orderByDesc('sar.created_at')->limit($limit)->get()->toArray();
    }

    public function getExpiringClearances(int $limit = 10): array
    {
        return DB::table('user_security_clearance as usc')
            ->join('users as u', 'usc.user_id', '=', 'u.id')
            ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->whereNotNull('usc.expires_at')->where('usc.expires_at', '<=', now()->addDays(30))->where('usc.expires_at', '>', now())
            ->select('usc.*', 'u.username', 'u.id as user_id', 'sc.name as clearance_name', 'sc.color',
                DB::raw("EXTRACT(DAY FROM usc.expires_at - NOW()) as days_remaining"))
            ->orderBy('usc.expires_at')->limit($limit)->get()->toArray();
    }

    // === Report Statistics ===

    public function getReportStats(string $period = '30 days'): array
    {
        $since = now()->sub(\DateInterval::createFromDateString($period));
        $clearanceStats = [
            'total_users' => DB::table('users')->where('active', true)->count(),
            'with_clearance' => DB::table('user_security_clearance')->distinct('user_id')->count('user_id'),
            'without_clearance' => DB::table('users')->where('active', true)
                ->whereNotIn('id', function ($q) { $q->select('user_id')->from('user_security_clearance'); })->count(),
        ];
        $clearancesByLevel = DB::table('user_security_clearance as usc')
            ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->select('sc.name', 'sc.code', 'sc.color', 'sc.level', DB::raw('COUNT(*) as count'))
            ->groupBy('sc.id', 'sc.name', 'sc.code', 'sc.color', 'sc.level')->orderBy('sc.level')->get()->toArray();
        $objectsByLevel = DB::table('object_security_classification as osc')
            ->join('security_classifications as sc', 'osc.classification_id', '=', 'sc.id')
            ->where('osc.active', true)->select('sc.name', 'sc.code', 'sc.color', 'sc.level', DB::raw('COUNT(*) as count'))
            ->groupBy('sc.id', 'sc.name', 'sc.code', 'sc.color', 'sc.level')->orderBy('sc.level')->get()->toArray();
        $requestStats = [
            'pending' => DB::table('security_access_requests')->where('status', 'pending')->count(),
            'approved' => DB::table('security_access_requests')->where('status', 'approved')->where('updated_at', '>=', $since)->count(),
            'denied' => DB::table('security_access_requests')->where('status', 'denied')->where('updated_at', '>=', $since)->count(),
        ];
        return compact('clearanceStats', 'clearancesByLevel', 'objectsByLevel', 'requestStats');
    }

    // === Security Compliance ===

    public function getComplianceStats(): array
    {
        $stats = ['classified_objects' => 0, 'pending_reviews' => 0, 'cleared_users' => 0, 'access_logs_today' => 0];
        try {
            $stats['classified_objects'] = DB::table('object_security_classification')->where('active', true)->count();
            $stats['cleared_users'] = DB::table('user_security_clearance')->count();
            $stats['access_logs_today'] = DB::table('user_security_clearance_log')->whereDate('created_at', today())->count();
        } catch (\Exception) {}
        return $stats;
    }

    public function getRecentComplianceLogs(int $limit = 10): array
    {
        return DB::table('user_security_clearance_log')->orderByDesc('created_at')->limit($limit)->get()->toArray();
    }

    // === Audit Logging ===

    public function logSecurityAudit(int $userId, ?string $objectIri, string $action, array $details = []): void
    {
        $username = DB::table('users')->where('id', $userId)->value('username');
        DB::table('audit_log')->insert([
            'uuid' => Str::uuid()->toString(), 'user_id' => $userId, 'username' => $username,
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
            'action' => $action, 'entity_type' => 'SecurityClassification', 'entity_id' => $objectIri,
            'module' => 'security', 'action_name' => $action, 'new_values' => json_encode($details),
            'created_at' => now(),
        ]);
    }

    public function getAuditLog(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('audit_log')->where('module', 'security');
        if (!empty($filters['username'])) $query->where('username', 'ILIKE', '%' . $filters['username'] . '%');
        if (!empty($filters['action'])) $query->where('action', $filters['action']);
        if (!empty($filters['date_from'])) $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        if (!empty($filters['date_to'])) $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        $total = $query->count();
        $logs = $query->orderByDesc('created_at')->limit($limit)->offset($offset)->get();
        return compact('logs', 'total');
    }

    public function exportAuditLog(int $limit = 10000): Collection
    {
        return DB::table('audit_log')->where('module', 'security')
            ->select('created_at', 'username', 'action', 'entity_id as object_iri', 'ip_address')
            ->orderByDesc('created_at')->limit($limit)->get();
    }
}
