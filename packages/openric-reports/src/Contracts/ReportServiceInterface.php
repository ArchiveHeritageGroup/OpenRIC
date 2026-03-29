<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Contracts;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporting service interface -- SPARQL + PostgreSQL stats, entity reports, CSV export.
 *
 * Adapted from Heratio ahg-reports ReportService (483 LOC).
 * All queries use PostgreSQL (ILIKE, pg_size_pretty) and Fuseki SPARQL.
 */
interface ReportServiceInterface
{
    // ── Dashboard ────────────────────────────────────────────────────

    /**
     * Aggregate dashboard statistics: entity counts, publication status, recent updates.
     *
     * @return array<string, int|string>
     */
    public function getDashboardStats(): array;

    // ── Entity Reports ───────────────────────────────────────────────

    /**
     * Report on record sets (archival descriptions / RecordSet / Record).
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, level, publicationStatus, repositoryId, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportDescriptions(array $params): array;

    /**
     * Report on agents (Person, Family, CorporateBody).
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, entityType, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportAgents(array $params): array;

    /**
     * Report on repositories (holding institutions).
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportRepositories(array $params): array;

    /**
     * Report on accessions.
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportAccessions(array $params): array;

    /**
     * Report on donors.
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportDonors(array $params): array;

    /**
     * Report on physical storage (locations, containers).
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, dateOf, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportPhysicalStorage(array $params): array;

    /**
     * Report on taxonomies and controlled vocabularies.
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, sort, culture, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportTaxonomies(array $params): array;

    /**
     * Report on recently updated entities.
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, entityType, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function reportUpdates(array $params): array;

    /**
     * Report on user activity from audit log.
     *
     * @param array<string, mixed> $params  dateStart, dateEnd, actionUser, userAction, limit, page
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int, auditTable: ?string}
     */
    public function reportUserActivity(array $params): array;

    /**
     * Report on access restrictions, embargoes, rights.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function reportAccess(array $params): array;

    /**
     * Spatial analysis: records with geographic coordinates.
     *
     * @param array<string, mixed> $params  culture, placeIds, level, subjects, topLevelOnly, requireCoordinates
     * @return array{results: Collection, total: int}
     */
    public function reportSpatial(array $params): array;

    // ── SPARQL Statistics ────────────────────────────────────────────

    /**
     * Get entity creation statistics by period from triplestore.
     *
     * @return array{period: string, data: array<string, int>}
     */
    public function getCreationStats(string $period = 'month'): array;

    /**
     * Get collection-level statistics from triplestore.
     *
     * @return array<int, array{iri: string, title: string, records: int}>
     */
    public function getCollectionStats(): array;

    /**
     * Get search analytics from audit log.
     *
     * @return array{total_searches: int, recent_searches: int, top_search_terms: array}
     */
    public function getSearchStats(): array;

    // ── Lookup Helpers ───────────────────────────────────────────────

    /**
     * Get levels of description for filter dropdowns.
     */
    public function getLevelsOfDescription(string $culture = 'en'): Collection;

    /**
     * Get entity type terms for filter dropdowns.
     */
    public function getEntityTypes(string $culture = 'en'): Collection;

    /**
     * Get available cultures / locales.
     *
     * @return string[]
     */
    public function getAvailableCultures(): array;

    /**
     * Get distinct usernames from audit log for filter dropdowns.
     *
     * @return string[]
     */
    public function getAuditUsers(): array;

    /**
     * Get place terms for spatial analysis filter.
     */
    public function getPlaceTerms(string $culture = 'en'): Collection;

    /**
     * Get list of repositories for filter dropdowns.
     */
    public function getRepositoryList(string $culture = 'en'): Collection;

    // ── Export ────────────────────────────────────────────────────────

    /**
     * Stream a CSV download.
     *
     * @param array<int, object|array> $data
     * @param string[]                 $headers
     */
    public function exportCsv(array $data, array $headers, string $filename): StreamedResponse;

    /**
     * Export data as GeoJSON FeatureCollection.
     *
     * @param Collection $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportGeoJson(Collection $data): \Illuminate\Http\JsonResponse;
}
