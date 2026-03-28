<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenRiC\Condition\Contracts\ConditionServiceInterface;

/**
 * Condition assessment service — adapted from Heratio ConditionService (245 lines).
 *
 * Provides Spectrum 5.0 condition assessment with:
 *   - Assessment CRUD with history tracking
 *   - Photo management with upload/delete
 *   - Photo annotations (damage markers on images)
 *   - Admin statistics and condition breakdown
 *   - Assessment templates
 *   - Upcoming assessment scheduling
 *   - Export/report generation
 *
 * Heratio uses integer object_id; OpenRiC uses string object_iri (RDF).
 */
class ConditionService implements ConditionServiceInterface
{
    // =========================================================================
    // Assessment CRUD — from Heratio lines 50-130
    // =========================================================================

    /**
     * Create a new condition assessment.
     */
    public function assess(string $objectIri, array $data, int $userId): int
    {
        return DB::table('condition_assessments')->insertGetId([
            'object_iri' => $objectIri,
            'assessed_by' => $userId,
            'assessed_at' => now(),
            'condition_code' => $data['condition_code'],
            'condition_label' => $data['condition_label'],
            'conservation_priority' => $data['conservation_priority'] ?? 0,
            'completeness_pct' => $data['completeness_pct'] ?? 100,
            'hazards' => isset($data['hazards']) ? json_encode($data['hazards']) : null,
            'storage_requirements' => $data['storage_requirements'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'notes' => $data['notes'] ?? null,
            'next_assessment_date' => $data['next_assessment_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get a single condition assessment by ID with assessor info.
     */
    public function find(int $id): ?object
    {
        $check = DB::table('condition_assessments as ca')
            ->leftJoin('users as u', 'ca.assessed_by', '=', 'u.id')
            ->where('ca.id', $id)
            ->select('ca.*', 'u.display_name as assessor_name', 'u.username as assessor_username')
            ->first();

        if ($check) {
            $check->photos = $this->getPhotosForCheck($id);
            $check->annotation_stats = $this->getAnnotationStats($id);
        }

        return $check;
    }

    /**
     * Get the latest assessment for an object.
     */
    public function getLatest(string $objectIri): ?array
    {
        $record = DB::table('condition_assessments')
            ->where('object_iri', $objectIri)
            ->orderByDesc('assessed_at')
            ->first();

        return $record ? (array) $record : null;
    }

    /**
     * Get assessment history for an object.
     */
    public function getHistory(string $objectIri): array
    {
        return DB::table('condition_assessments as ca')
            ->leftJoin('users as u', 'ca.assessed_by', '=', 'u.id')
            ->where('ca.object_iri', $objectIri)
            ->select('ca.*', 'u.display_name as assessor_name')
            ->orderByDesc('ca.assessed_at')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }

    /**
     * Get assessments due within N days.
     */
    public function getUpcoming(int $days = 30): array
    {
        return DB::table('condition_assessments')
            ->whereNotNull('next_assessment_date')
            ->where('next_assessment_date', '<=', now()->addDays($days))
            ->where('next_assessment_date', '>=', now())
            ->orderBy('next_assessment_date')
            ->limit(50)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }

    /**
     * Browse assessments with filters and pagination.
     */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('condition_assessments as ca')
            ->leftJoin('users as u', 'ca.assessed_by', '=', 'u.id')
            ->select('ca.*', 'u.display_name as assessor_name');

        if (!empty($filters['condition_code'])) {
            $query->where('ca.condition_code', $filters['condition_code']);
        }
        if (!empty($filters['object_iri'])) {
            $query->where('ca.object_iri', $filters['object_iri']);
        }
        if (!empty($filters['assessed_by'])) {
            $query->where('ca.assessed_by', $filters['assessed_by']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('ca.assessed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('ca.assessed_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['priority_min'])) {
            $query->where('ca.conservation_priority', '>=', (int) $filters['priority_min']);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('ca.assessed_at')->limit($limit)->offset($offset)->get()->map(fn ($r) => (array) $r)->toArray();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get recent condition checks — from Heratio getRecentChecks().
     */
    public function getRecentChecks(int $limit = 20): array
    {
        return DB::table('condition_assessments as ca')
            ->leftJoin('users as u', 'ca.assessed_by', '=', 'u.id')
            ->select('ca.*', 'u.display_name as assessor_name')
            ->orderByDesc('ca.assessed_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // Admin Statistics — from Heratio getAdminStats(), getByConditionBreakdown()
    // =========================================================================

    /**
     * Get admin dashboard statistics.
     */
    public function getAdminStats(): array
    {
        $totalChecks = DB::table('condition_assessments')->count();
        $totalPhotos = DB::table('condition_assessment_history')->count();

        $annotatedPhotos = DB::table('condition_assessment_history')
            ->whereNotNull('annotations')
            ->where('annotations', '!=', '[]')
            ->count();

        return [
            'total_checks' => $totalChecks,
            'total_photos' => $totalPhotos,
            'annotated_photos' => $annotatedPhotos,
            'overdue_assessments' => DB::table('condition_assessments')
                ->whereNotNull('next_assessment_date')
                ->where('next_assessment_date', '<', now())
                ->count(),
            'high_priority' => DB::table('condition_assessments')
                ->where('conservation_priority', '>=', 4)
                ->count(),
        ];
    }

    /**
     * Get condition breakdown by status.
     */
    public function getConditionBreakdown(): array
    {
        return DB::table('condition_assessments')
            ->select('condition_code', 'condition_label', DB::raw('COUNT(*) as count'))
            ->groupBy('condition_code', 'condition_label')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // Photo Management — from Heratio lines 130-200
    // =========================================================================

    /**
     * Get photos for a condition check.
     */
    public function getPhotosForCheck(int $checkId): Collection
    {
        return DB::table('condition_assessment_history')
            ->where('assessment_id', $checkId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get a single photo.
     */
    public function getPhoto(int $photoId): ?object
    {
        return DB::table('condition_assessment_history')->find($photoId);
    }

    /**
     * Upload a photo for a condition check.
     */
    public function uploadPhoto(int $checkId, $file, string $photoType, string $caption, int $userId): int
    {
        $path = $file->store('condition-photos/' . $checkId, 'public');

        return DB::table('condition_assessment_history')->insertGetId([
            'assessment_id' => $checkId,
            'photo_path' => $path,
            'photo_type' => $photoType,
            'caption' => $caption,
            'uploaded_by' => $userId,
            'annotations' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Delete a photo.
     */
    public function deletePhoto(int $photoId, int $userId): bool
    {
        $photo = $this->getPhoto($photoId);
        if (!$photo) {
            return false;
        }

        // Delete file from storage
        if (!empty($photo->photo_path)) {
            Storage::disk('public')->delete($photo->photo_path);
        }

        return DB::table('condition_assessment_history')->where('id', $photoId)->delete() > 0;
    }

    // =========================================================================
    // Annotations — from Heratio lines 200-240
    // =========================================================================

    /**
     * Get annotations for a photo.
     */
    public function getAnnotations(int $photoId): array
    {
        $photo = $this->getPhoto($photoId);
        if (!$photo || empty($photo->annotations)) {
            return [];
        }

        return json_decode($photo->annotations, true) ?: [];
    }

    /**
     * Save annotations for a photo.
     *
     * @param array $annotations Array of {x, y, width, height, type, label, notes}
     */
    public function saveAnnotations(int $photoId, array $annotations, int $userId): bool
    {
        return DB::table('condition_assessment_history')
            ->where('id', $photoId)
            ->update([
                'annotations' => json_encode($annotations),
                'annotated_by' => $userId,
                'annotated_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get annotation statistics for a check.
     */
    public function getAnnotationStats(int $checkId): array
    {
        $photos = DB::table('condition_assessment_history')
            ->where('assessment_id', $checkId)
            ->get();

        $totalPhotos = $photos->count();
        $annotatedPhotos = $photos->filter(fn ($p) => !empty($p->annotations) && $p->annotations !== '[]')->count();
        $totalAnnotations = $photos->sum(function ($p) {
            $annotations = json_decode($p->annotations ?? '[]', true);
            return is_array($annotations) ? count($annotations) : 0;
        });

        return [
            'total_photos' => $totalPhotos,
            'annotated_photos' => $annotatedPhotos,
            'total_annotations' => $totalAnnotations,
        ];
    }

    // =========================================================================
    // Templates — from Heratio getTemplates(), getTemplateView()
    // =========================================================================

    /**
     * Get condition assessment templates.
     */
    public function getTemplates(): array
    {
        return [
            ['id' => 1, 'name' => 'General Paper Document', 'fields' => ['condition_code', 'completeness_pct', 'hazards', 'storage_requirements'], 'condition_options' => ['excellent', 'good', 'fair', 'poor', 'critical']],
            ['id' => 2, 'name' => 'Photographic Material', 'fields' => ['condition_code', 'completeness_pct', 'hazards', 'storage_requirements', 'color_fading', 'emulsion_damage'], 'condition_options' => ['excellent', 'good', 'fair', 'poor', 'critical']],
            ['id' => 3, 'name' => 'Bound Volume', 'fields' => ['condition_code', 'binding_condition', 'spine_condition', 'completeness_pct', 'page_damage'], 'condition_options' => ['excellent', 'good', 'fair', 'poor', 'critical']],
            ['id' => 4, 'name' => 'Digital Media', 'fields' => ['condition_code', 'media_type', 'readability', 'format_obsolescence'], 'condition_options' => ['accessible', 'partially_accessible', 'at_risk', 'inaccessible']],
            ['id' => 5, 'name' => 'Museum Object / Artefact', 'fields' => ['condition_code', 'completeness_pct', 'structural_integrity', 'surface_condition', 'hazards'], 'condition_options' => ['excellent', 'good', 'fair', 'poor', 'critical']],
        ];
    }

    /**
     * Get a single template by ID.
     */
    public function getTemplate(int $id): ?array
    {
        $templates = $this->getTemplates();
        foreach ($templates as $t) {
            if ($t['id'] === $id) {
                return $t;
            }
        }
        return null;
    }

    // =========================================================================
    // Export — from Heratio exportReport()
    // =========================================================================

    /**
     * Generate a condition report for export.
     */
    public function generateReport(int $checkId): ?array
    {
        $check = $this->find($checkId);
        if (!$check) {
            return null;
        }

        return [
            'assessment' => (array) $check,
            'photos' => $check->photos->toArray(),
            'annotations' => $check->photos->map(fn ($p) => [
                'photo_id' => $p->id,
                'caption' => $p->caption,
                'annotations' => json_decode($p->annotations ?? '[]', true),
            ])->toArray(),
            'generated_at' => now()->toISOString(),
        ];
    }
}
