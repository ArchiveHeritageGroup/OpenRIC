<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Auth\Contracts\SecurityClearanceServiceInterface;
use OpenRiC\Auth\Models\SecurityClassification;
use OpenRiC\Auth\Models\UserSecurityClearance;

class SecurityClearanceService implements SecurityClearanceServiceInterface
{
    public function getUserClearance(int $userId): ?SecurityClassification
    {
        $clearance = UserSecurityClearance::where('user_id', $userId)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->first();

        if ($clearance === null) {
            return null;
        }

        return $clearance->classification;
    }

    public function getUserClearanceLevel(int $userId): int
    {
        $classification = $this->getUserClearance($userId);

        return $classification?->level ?? 0;
    }

    public function canAccessObject(int $userId, string $objectIri): bool
    {
        $objectClassification = $this->getObjectClassification($objectIri);

        if ($objectClassification === null) {
            return true;
        }

        $userLevel = $this->getUserClearanceLevel($userId);

        return $userLevel >= $objectClassification->level;
    }

    public function grantClearance(int $userId, int $classificationId, int $grantedBy, ?string $expiresAt = null, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($userId, $classificationId, $grantedBy, $expiresAt, $notes) {
            $existing = UserSecurityClearance::where('user_id', $userId)->first();
            $previousClassificationId = $existing?->classification_id;

            UserSecurityClearance::updateOrCreate(
                ['user_id' => $userId],
                [
                    'classification_id' => $classificationId,
                    'granted_by' => $grantedBy,
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                    'notes' => $notes,
                ]
            );

            DB::table('user_security_clearance_log')->insert([
                'user_id' => $userId,
                'action' => $previousClassificationId ? 'updated' : 'granted',
                'previous_classification_id' => $previousClassificationId,
                'classification_id' => $classificationId,
                'changed_by' => $grantedBy,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return true;
        });
    }

    public function revokeClearance(int $userId, int $revokedBy, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($userId, $revokedBy, $notes) {
            $existing = UserSecurityClearance::where('user_id', $userId)->first();

            if ($existing === null) {
                return false;
            }

            DB::table('user_security_clearance_log')->insert([
                'user_id' => $userId,
                'action' => 'revoked',
                'previous_classification_id' => $existing->classification_id,
                'classification_id' => null,
                'changed_by' => $revokedBy,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $existing->delete();

            return true;
        });
    }

    public function classifyObject(string $objectIri, int $classificationId, int $userId, ?string $reason = null): bool
    {
        DB::table('object_security_classification')
            ->where('object_iri', $objectIri)
            ->where('active', true)
            ->update(['active' => false]);

        DB::table('object_security_classification')->insert([
            'object_iri' => $objectIri,
            'classification_id' => $classificationId,
            'classified_by' => $userId,
            'classified_at' => now(),
            'reason' => $reason,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function getObjectClassification(string $objectIri): ?SecurityClassification
    {
        $record = DB::table('object_security_classification')
            ->where('object_iri', $objectIri)
            ->where('active', true)
            ->first();

        if ($record === null) {
            return null;
        }

        return SecurityClassification::find($record->classification_id);
    }
}
