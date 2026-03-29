<?php

declare(strict_types=1);

namespace OpenRiC\Spectrum\Contracts;

/**
 * Interface for the Spectrum 5.1 collections management service.
 *
 * Defines all 21 Spectrum 5.1 procedures and supporting operations
 * for workflow management, condition checking, and statistics.
 */
interface SpectrumServiceInterface
{
    // ------------------------------------------------------------------
    // Procedure definitions
    // ------------------------------------------------------------------

    /**
     * Return the full definition array for all 21 Spectrum 5.1 procedures.
     *
     * Each key is a procedure constant (e.g. 'object_entry') and each value
     * is an associative array with keys: label, description, category, icon.
     *
     * @return array<string, array{label: string, description: string, category: string, icon: string}>
     */
    public function getProcedures(): array;

    /**
     * Return the definition for a single procedure by key.
     *
     * @return array{label: string, description: string, category: string, icon: string}|null
     */
    public function getProcedure(string $procedureKey): ?array;

    /**
     * Return procedure keys grouped by category.
     *
     * @return array<string, string[]>
     */
    public function getProceduresByCategory(): array;

    // ------------------------------------------------------------------
    // Status / workflow
    // ------------------------------------------------------------------

    /**
     * Return the list of valid status constants.
     *
     * @return string[]
     */
    public function getStatuses(): array;

    /**
     * Return hex colour codes keyed by status constant.
     *
     * @return array<string, string>
     */
    public function getStatusColors(): array;

    /**
     * Return human-readable labels keyed by status constant.
     *
     * @return array<string, string>
     */
    public function getStatusLabels(): array;

    /**
     * Get the current workflow state for a record + procedure combination.
     *
     * @return object|null  Row from spectrum_workflow_state or null.
     */
    public function getWorkflowState(int $recordId, string $procedureType): ?object;

    /**
     * Get all workflow states for a given record.
     *
     * @return array<string, object>  Keyed by procedure_type.
     */
    public function getWorkflowStatesForRecord(int $recordId): array;

    /**
     * Get the workflow configuration (JSON config) for a procedure type.
     *
     * @return array|null  Decoded config_json or null.
     */
    public function getWorkflowConfig(string $procedureType): ?array;

    /**
     * Return the configured final states for a procedure type.
     *
     * @return string[]
     */
    public function getFinalStates(string $procedureType): array;

    /**
     * Check whether a state is a terminal state for a procedure.
     */
    public function isFinalState(string $procedureType, string $state): bool;

    /**
     * Get available transitions from a given state for a procedure.
     *
     * @return array<string, array{from: string[], to: string}>
     */
    public function getAvailableTransitions(string $procedureType, string $currentState): array;

    /**
     * Execute a workflow transition.
     *
     * @return bool  True on success.
     */
    public function executeTransition(
        int $recordId,
        string $procedureType,
        string $transitionKey,
        string $fromState,
        string $toState,
        ?int $assignedTo = null,
        ?string $note = null
    ): bool;

    // ------------------------------------------------------------------
    // Condition checking
    // ------------------------------------------------------------------

    /**
     * Get or create a condition check record for a given object.
     */
    public function getOrCreateConditionCheck(int $objectId): ?object;

    /**
     * Get all condition checks for an object, most recent first.
     *
     * @return object[]
     */
    public function getConditionChecksForObject(int $objectId): array;

    /**
     * Get condition photos for a condition check.
     *
     * @return array{photos: array, photosByType: array<string, array>}
     */
    public function getConditionPhotos(int $conditionCheckId): array;

    /**
     * Get high-risk items (condition = critical or poor).
     *
     * @return object[]
     */
    public function getRiskItems(): array;

    /**
     * Get condition statistics (total checks, critical count, poor count).
     *
     * @return array{total_checks: int, critical: int, poor: int}
     */
    public function getConditionStats(): array;

    // ------------------------------------------------------------------
    // Dashboard / statistics
    // ------------------------------------------------------------------

    /**
     * Get aggregate workflow statistics (totals, completed, in-progress, pending).
     *
     * @return array{total_objects: int, objects_with_workflows: int, completed_procedures: int, in_progress_procedures: int, pending_procedures: int}
     */
    public function getWorkflowStatistics(?int $repositoryId = null): array;

    /**
     * Get recent workflow activity entries.
     *
     * @return object[]
     */
    public function getRecentWorkflowActivity(?int $repositoryId = null, int $limit = 20): array;

    /**
     * Get procedure status counts grouped by procedure_type and current_state.
     *
     * @return array<string, array<string, int>>
     */
    public function getProcedureStatusCounts(?int $repositoryId = null): array;

    /**
     * Calculate overall completion percentage.
     *
     * @return array{percentage: int, completed: int, total: int}
     */
    public function calculateOverallCompletion(?int $repositoryId = null): array;

    /**
     * Get data quality metrics.
     *
     * @return array{totalObjects: int, missingTitles: int, missingDates: int, missingRepository: int, missingDigitalObjects: int, qualityScore: int}
     */
    public function getDataQualityMetrics(): array;

    // ------------------------------------------------------------------
    // Browse queries
    // ------------------------------------------------------------------

    /**
     * Get paginated object entry records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getObjectEntries(int $perPage = 25): mixed;

    /**
     * Get paginated acquisition records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAcquisitions(int $perPage = 25): mixed;

    /**
     * Get loans in and loans out.
     *
     * @return array{loansIn: \Illuminate\Support\Collection, loansOut: \Illuminate\Support\Collection}
     */
    public function getLoans(): array;

    /**
     * Get paginated movement records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getMovements(int $perPage = 25): mixed;

    /**
     * Get paginated condition check records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getConditionChecks(int $perPage = 25): mixed;

    /**
     * Get paginated conservation treatment records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getConservationTreatments(int $perPage = 25): mixed;

    /**
     * Get paginated valuation records.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getValuations(int $perPage = 25): mixed;

    // ------------------------------------------------------------------
    // Export
    // ------------------------------------------------------------------

    /**
     * Export Spectrum data as CSV or JSON.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function exportData(string $format, string $type, ?int $objectId = null): mixed;

    // ------------------------------------------------------------------
    // Repositories filter
    // ------------------------------------------------------------------

    /**
     * Get repositories for filter dropdowns.
     *
     * @return object[]
     */
    public function getRepositoriesForFilter(): array;

    // ------------------------------------------------------------------
    // Task management
    // ------------------------------------------------------------------

    /**
     * Get tasks assigned to a user, excluding final-state tasks.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTasksForUser(int $userId, ?string $procedureTypeFilter = null): mixed;

    /**
     * Get distinct procedure types that have tasks assigned to a user.
     *
     * @return string[]
     */
    public function getAssignedProcedureTypes(int $userId): array;

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadNotificationCount(int $userId): int;
}
