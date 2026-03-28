<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Rights\Contracts\RightsServiceInterface;

/**
 * RightsService — rights statements, embargoes, and TK Labels for archival entities.
 *
 * Adapted from Heratio ahg-extended-rights ExtendedRightsService (500+ lines)
 * and EmbargoService (905 lines). Consolidated into a single service operating
 * on PostgreSQL tables: rights_statements, embargoes, tk_labels.
 */
class RightsService implements RightsServiceInterface
{
    // Embargo type constants (from Heratio EmbargoService)
    public const TYPE_FULL = 'full';
    public const TYPE_METADATA_ONLY = 'metadata_only';
    public const TYPE_DIGITAL_ONLY = 'digital_only';
    public const TYPE_PARTIAL = 'partial';

    // =========================================================================
    // RIGHTS STATEMENTS
    // =========================================================================

    public function getRightsForEntity(string $entityIri): array
    {
        return DB::table('rights_statements')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function getRightsStatement(int $id): ?object
    {
        return DB::table('rights_statements')->where('id', $id)->first();
    }

    public function createRightsStatement(array $data): int
    {
        $statementId = DB::table('rights_statements')->insertGetId([
            'entity_iri'        => $data['entity_iri'],
            'rights_basis'      => $data['rights_basis'],
            'rights_holder_name' => $data['rights_holder_name'] ?? null,
            'rights_holder_iri' => $data['rights_holder_iri'] ?? null,
            'start_date'        => $data['start_date'] ?? null,
            'end_date'          => $data['end_date'] ?? null,
            'documentation_iri' => $data['documentation_iri'] ?? null,
            'terms'             => $data['terms'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'created_by'        => $data['created_by'] ?? auth()->id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->logAudit('create', 'RightsStatement', $statementId, [], $data);

        return $statementId;
    }

    public function updateRightsStatement(int $id, array $data): bool
    {
        $old = DB::table('rights_statements')->where('id', $id)->first();
        if (!$old) {
            return false;
        }

        $updateData = array_filter([
            'rights_basis'       => $data['rights_basis'] ?? null,
            'rights_holder_name' => $data['rights_holder_name'] ?? null,
            'rights_holder_iri'  => $data['rights_holder_iri'] ?? null,
            'start_date'         => $data['start_date'] ?? null,
            'end_date'           => $data['end_date'] ?? null,
            'documentation_iri'  => $data['documentation_iri'] ?? null,
            'terms'              => $data['terms'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'updated_at'         => now(),
        ], fn ($v) => $v !== null);

        $updated = DB::table('rights_statements')->where('id', $id)->update($updateData) > 0;

        if ($updated) {
            $this->logAudit('update', 'RightsStatement', $id, (array) $old, $updateData);
        }

        return $updated;
    }

    public function deleteRightsStatement(int $id): bool
    {
        $old = DB::table('rights_statements')->where('id', $id)->first();
        if (!$old) {
            return false;
        }

        $deleted = DB::table('rights_statements')->where('id', $id)->delete() > 0;

        if ($deleted) {
            $this->logAudit('delete', 'RightsStatement', $id, (array) $old, []);
        }

        return $deleted;
    }

    // =========================================================================
    // EMBARGOES
    // =========================================================================

    public function getEmbargoes(string $entityIri): array
    {
        return DB::table('embargoes')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function getActiveEmbargo(string $entityIri): ?object
    {
        $now = now()->toDateString();

        return DB::table('embargoes')
            ->where('entity_iri', $entityIri)
            ->where('status', 'active')
            ->where('embargo_start', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('embargo_end')
                  ->orWhere('embargo_end', '>=', $now);
            })
            ->first();
    }

    public function createEmbargo(array $data): int
    {
        $startDate = $data['embargo_start'] ?? now()->toDateString();
        $status = strtotime($startDate) <= time() ? 'active' : 'pending';

        $embargoId = DB::table('embargoes')->insertGetId([
            'entity_iri'   => $data['entity_iri'],
            'reason'       => $data['reason'] ?? null,
            'embargo_start' => $startDate,
            'embargo_end'  => $data['embargo_end'] ?? null,
            'status'       => $data['status'] ?? $status,
            'lifted_by'    => null,
            'lifted_at'    => null,
            'created_by'   => $data['created_by'] ?? auth()->id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->logAudit('create', 'Embargo', $embargoId, [], $data);

        return $embargoId;
    }

    public function liftEmbargo(int $id, int $userId, ?string $reason = null): bool
    {
        $old = DB::table('embargoes')->where('id', $id)->first();
        if (!$old) {
            return false;
        }

        $lifted = DB::table('embargoes')->where('id', $id)->update([
            'status'    => 'lifted',
            'lifted_by' => $userId,
            'lifted_at' => now(),
            'updated_at' => now(),
        ]) > 0;

        if ($lifted) {
            $this->logAudit('lift', 'Embargo', $id, (array) $old, [
                'status'    => 'lifted',
                'lifted_by' => $userId,
                'reason'    => $reason,
            ]);
        }

        return $lifted;
    }

    public function isEmbargoed(string $entityIri): bool
    {
        return $this->getActiveEmbargo($entityIri) !== null;
    }

    /**
     * Process expired embargoes — set status to 'expired' for embargoes past their end date.
     *
     * Adapted from Heratio EmbargoProcessCommand.
     */
    public function processExpiredEmbargoes(): int
    {
        $now = now()->toDateString();

        return DB::table('embargoes')
            ->where('status', 'active')
            ->whereNotNull('embargo_end')
            ->where('embargo_end', '<', $now)
            ->update([
                'status'     => 'expired',
                'updated_at' => now(),
            ]);
    }

    /**
     * Get embargoes expiring within the given number of days.
     *
     * Adapted from Heratio EmbargoService::getExpiringEmbargoes.
     *
     * @return array<int, object>
     */
    public function getExpiringEmbargoes(int $days = 30): array
    {
        $now = now()->toDateString();
        $future = now()->addDays($days)->toDateString();

        return DB::table('embargoes')
            ->where('status', 'active')
            ->whereNotNull('embargo_end')
            ->whereBetween('embargo_end', [$now, $future])
            ->orderBy('embargo_end')
            ->get()
            ->toArray();
    }

    /**
     * Get embargo display info for a blocked page (public-safe).
     *
     * Adapted from Heratio EmbargoService::getEmbargoDisplayInfo.
     */
    public function getEmbargoDisplayInfo(string $entityIri): ?array
    {
        $embargo = $this->getActiveEmbargo($entityIri);
        if (!$embargo) {
            return null;
        }

        return [
            'type'           => $embargo->status,
            'public_message' => $embargo->reason,
            'end_date'       => $embargo->embargo_end,
            'is_perpetual'   => $embargo->embargo_end === null,
        ];
    }

    // =========================================================================
    // TK LABELS
    // =========================================================================

    public function getTkLabels(string $entityIri): array
    {
        return DB::table('tk_labels')
            ->where('entity_iri', $entityIri)
            ->orderBy('label_type')
            ->get()
            ->toArray();
    }

    public function assignTkLabel(array $data): int
    {
        $labelId = DB::table('tk_labels')->insertGetId([
            'entity_iri'  => $data['entity_iri'],
            'label_type'  => $data['label_type'],
            'label_iri'   => $data['label_iri'] ?? null,
            'assigned_by' => $data['assigned_by'] ?? auth()->id(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->logAudit('assign', 'TkLabel', $labelId, [], $data);

        return $labelId;
    }

    public function removeTkLabel(int $id): bool
    {
        $old = DB::table('tk_labels')->where('id', $id)->first();
        if (!$old) {
            return false;
        }

        $deleted = DB::table('tk_labels')->where('id', $id)->delete() > 0;

        if ($deleted) {
            $this->logAudit('remove', 'TkLabel', $id, (array) $old, []);
        }

        return $deleted;
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    public function getRightsStats(): array
    {
        $statementsByBasis = DB::table('rights_statements')
            ->selectRaw('rights_basis, COUNT(*) as count')
            ->groupBy('rights_basis')
            ->pluck('count', 'rights_basis')
            ->toArray();

        $activeEmbargoes = (int) DB::table('embargoes')->where('status', 'active')->count();
        $liftedEmbargoes = (int) DB::table('embargoes')->where('status', 'lifted')->count();
        $expiredEmbargoes = (int) DB::table('embargoes')->where('status', 'expired')->count();
        $totalTkLabels = (int) DB::table('tk_labels')->count();

        return [
            'total_statements'    => (int) DB::table('rights_statements')->count(),
            'statements_by_basis' => $statementsByBasis,
            'active_embargoes'    => $activeEmbargoes,
            'lifted_embargoes'    => $liftedEmbargoes,
            'expired_embargoes'   => $expiredEmbargoes,
            'total_tk_labels'     => $totalTkLabels,
        ];
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues): void
    {
        try {
            DB::table('audit_log')->insert([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => (string) $entityId,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            // Non-critical
        }
    }
}
