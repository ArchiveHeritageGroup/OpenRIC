<?php

declare(strict_types=1);

namespace OpenRiC\Repository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Repository (archival institution) management — ISDIAH compliant.
 *
 * Adapted from Heratio ahg-repository-manage RepositoryService + RepositoryBrowseService.
 * Repositories are stored in PostgreSQL using the AtoM class-table-inheritance schema:
 *   object -> actor -> repository  (with actor_i18n + repository_i18n for translations)
 */
interface RepositoryServiceInterface
{
    // ─── CRUD ────────────────────────────────────────────────────────────

    /**
     * Get a repository by its URL slug, with all ISDIAH + ISAAR fields.
     */
    public function getBySlug(string $slug): ?object;

    /**
     * Get a repository by primary key with full class-table-inheritance join.
     */
    public function getById(int $id): ?object;

    /**
     * Create a new repository with all related data.
     *
     * @return int  The new repository ID.
     */
    public function create(array $data): int;

    /**
     * Update an existing repository.
     */
    public function update(int $id, array $data): void;

    /**
     * Delete a repository and all related data (contacts, names, notes, slugs).
     */
    public function delete(int $id): void;

    /**
     * Get the slug for a repository ID.
     */
    public function getSlug(int $id): ?string;

    // ─── Related data retrieval ──────────────────────────────────────────

    /**
     * Get contact information records for a repository.
     */
    public function getContacts(int $repoId): Collection;

    /**
     * Get digital objects associated with a repository.
     *
     * @return array<int, array>
     */
    public function getDigitalObjects(int $repoId): array;

    /**
     * Count information objects (holdings) linked to a repository.
     */
    public function getHoldingsCount(int $repoId): int;

    /**
     * Paginated top-level holdings (information objects) for a repository.
     */
    public function getHoldingsPaginated(int $repoId, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    /**
     * Resolve a term name by ID using the current locale.
     */
    public function getTermName(?int $termId): ?string;

    /**
     * Get other names (parallel forms, other forms) for a repository.
     */
    public function getOtherNames(int $repoId): Collection;

    /**
     * Get repository type(s) from the object_term_relation table (taxonomy 38).
     */
    public function getRepositoryTypes(int $repoId): Collection;

    /**
     * Get language(s) from the property table.
     *
     * @return array<int, string>
     */
    public function getLanguages(int $repoId): array;

    /**
     * Get script(s) from the property table.
     *
     * @return array<int, string>
     */
    public function getScripts(int $repoId): array;

    /**
     * Get maintenance notes (note table, type_id 174).
     */
    public function getMaintenanceNotes(int $repoId): ?string;

    /**
     * Get thematic area access points (taxonomy 72).
     */
    public function getThematicAreas(int $repoId): Collection;

    /**
     * Get geographic subregion access points (taxonomy 73).
     */
    public function getGeographicSubregions(int $repoId): Collection;

    /**
     * Get maintained actors (authority records) linked to a repository.
     *
     * @return array{label: string, moreUrl: string, dataUrl: string, pager: LengthAwarePaginator, items: Collection}|null
     */
    public function getMaintainedActors(int $repoId, int $perPage = 10, int $page = 1): ?array;

    /**
     * Get form dropdown choices for the create/edit form.
     *
     * @return array{descriptionStatuses: Collection, descriptionDetails: Collection}
     */
    public function getFormChoices(): array;

    /**
     * Update repository theme settings (background colour, HTML snippet, banner, logo).
     */
    public function updateTheme(int $id, array $data, $request = null): void;

    /**
     * Get a scoped repository setting value.
     */
    public function getRepositorySetting(int $repositoryId, string $name): ?string;

    // ─── Browse / faceted search ─────────────────────────────────────────

    /**
     * Browse repositories with basic pagination + search.
     *
     * @param  array{page?: int, limit?: int, sort?: string, sortDir?: string, subquery?: string} $params
     * @return array{hits: array, total: int, page: int, limit: int}
     */
    public function browse(array $params): array;

    /**
     * Browse repositories with advanced filters (thematic area, region, type, etc.).
     *
     * @param  array{page?: int, limit?: int, sort?: string, sortDir?: string, subquery?: string, thematicArea?: string, region?: string, locality?: string, archiveType?: string, subregion?: string, hasDigitalObject?: string, languages?: string} $params
     * @return array{hits: array, total: int, page: int, limit: int}
     */
    public function browseAdvanced(array $params): array;

    /**
     * Get thematic area facet counts for the browse sidebar.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function getThematicAreaFacets(): array;

    /**
     * Get geographic region facet counts for the browse sidebar.
     *
     * @return array<int, object>
     */
    public function getRegionFacets(): array;

    /**
     * Get archive type facet counts for the browse sidebar.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function getArchiveTypeFacets(): array;

    /**
     * Get geographic subregion facet counts for the browse sidebar.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function getSubregionFacets(): array;

    /**
     * Get language facet counts for the browse sidebar.
     *
     * @return array<string, array{name: string, count: int}>
     */
    public function getLanguageFacets(): array;

    /**
     * Get locality facet counts for the browse sidebar.
     *
     * @return array<string, array{name: string, count: int}>
     */
    public function getLocalityFacets(): array;
}
