<?php

declare(strict_types=1);

namespace OpenRiC\Label\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Label\Contracts\LabelServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Label service — adapted from Heratio ahg-label LabelController (372 lines).
 *
 * Heratio resolves entities via AtoM's slug → object → class_name → entity table → i18n table
 * chain across MySQL. OpenRiC resolves entities via IRI against the Fuseki triplestore
 * (TriplestoreServiceInterface) for archival/agent entities, and PostgreSQL operational
 * tables for accessions.
 *
 * Key adaptations:
 *   - Slug-based lookups replaced with IRI-based triplestore queries
 *   - QubitInformationObject → rico:RecordResource / rico:RecordSet
 *   - QubitActor / QubitRepository → rico:Agent / rico:CorporateBody
 *   - QubitAccession → OpenRiC accessions table (PostgreSQL)
 *   - i18n tables replaced with rico:title / rico:hasAgentName properties
 *   - display_object_config sector detection replaced with rico:type / entity properties
 *   - library_item fields replaced with triplestore property lookups (isbn, issn, etc.)
 *   - MySQL LIKE replaced with PostgreSQL ILIKE where applicable
 */
class LabelService implements LabelServiceInterface
{
    /**
     * Sector labels mapping — from Heratio LabelController::$sectorLabels.
     */
    private const SECTOR_LABELS = [
        'library' => 'Library Item',
        'archive' => 'Archival Record',
        'museum'  => 'Museum Object',
        'gallery' => 'Gallery Artwork',
    ];

    /**
     * Preferred barcode source order — from Heratio LabelController.
     * isbn > issn > barcode > accession > identifier > title
     */
    private const BARCODE_PRIORITY = ['isbn', 'issn', 'barcode', 'accession', 'identifier', 'title'];

    /**
     * RiC-O entity types that map to "record" entities (information objects in Heratio).
     */
    private const RECORD_TYPES = [
        'rico:RecordResource',
        'rico:Record',
        'rico:RecordSet',
        'rico:RecordPart',
    ];

    /**
     * RiC-O entity types that map to "agent" entities (actors/repositories in Heratio).
     */
    private const AGENT_TYPES = [
        'rico:Agent',
        'rico:Person',
        'rico:CorporateBody',
        'rico:Family',
        'rico:Group',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * Resolve an entity by IRI and return all label-relevant data.
     *
     * Adapted from Heratio LabelController::index() which resolves via slug → object → class
     * → entity table → i18n table. OpenRiC queries the triplestore for RiC entities and
     * falls back to PostgreSQL accessions table for accession entities.
     */
    public function resolveEntity(string $iri): ?array
    {
        // First, try the triplestore (records + agents)
        $properties = $this->triplestore->getEntity($iri);

        if ($properties !== null) {
            return $this->buildLabelDataFromTriplestore($iri, $properties);
        }

        // Fallback: check PostgreSQL accessions table
        $accession = DB::table('accessions')
            ->where('object_iri', $iri)
            ->first();

        if ($accession !== null) {
            return $this->buildLabelDataFromAccession($iri, $accession);
        }

        return null;
    }

    /**
     * Build label data from triplestore entity properties.
     *
     * Adapted from Heratio's entity type branching (QubitInformationObject / QubitActor)
     * which pulls title from i18n tables and identifier from entity tables. OpenRiC
     * reads rico:title, rico:identifier, rico:hasAgentName directly from the RDF graph.
     */
    private function buildLabelDataFromTriplestore(string $iri, array $properties): array
    {
        $entityType = $this->resolveEntityType($properties);
        $title = $this->extractTitle($properties, $entityType);
        $identifier = $this->extractIdentifier($properties);
        $sector = $this->detectSector($properties);
        $sectorLabel = self::SECTOR_LABELS[$sector] ?? 'Record';
        $barcodeSources = $this->extractBarcodeSources($properties, $entityType, $title, $identifier);
        $defaultBarcodeData = $this->selectDefaultBarcodeData($barcodeSources);
        $repositoryName = $this->resolveRepositoryName($properties, $entityType);
        $qrUrl = url('/record/' . urlencode($iri));

        return [
            'iri'                 => $iri,
            'title'               => $title,
            'identifier'          => $identifier,
            'entity_type'         => $entityType,
            'sector'              => $sector,
            'sector_label'        => $sectorLabel,
            'barcode_sources'     => $barcodeSources,
            'default_barcode_data' => $defaultBarcodeData,
            'repository_name'     => $repositoryName,
            'qr_url'              => $qrUrl,
        ];
    }

    /**
     * Build label data from a PostgreSQL accession row.
     *
     * Adapted from Heratio's QubitAccession branch which reads accession.identifier
     * and uses it for both title and barcode. OpenRiC accessions store accession_number,
     * title, and description directly in PostgreSQL.
     */
    private function buildLabelDataFromAccession(string $iri, object $accession): array
    {
        $title = $accession->title ?: $accession->accession_number;
        $identifier = $accession->accession_number;

        $barcodeSources = [];
        if (!empty($identifier)) {
            $barcodeSources['accession'] = [
                'label' => 'Accession Number',
                'value' => $identifier,
            ];
        }
        if (!empty($title) && $title !== $identifier) {
            $barcodeSources['title'] = [
                'label' => 'Title',
                'value' => $title,
            ];
        }

        // Look up donor as repository equivalent
        $repositoryName = '';
        if (!empty($accession->donor_id)) {
            $donor = DB::table('donors')->where('id', $accession->donor_id)->first();
            if ($donor) {
                $repositoryName = $donor->name ?? '';
            }
        }

        return [
            'iri'                 => $iri,
            'title'               => $title,
            'identifier'          => $identifier,
            'entity_type'         => 'Accession',
            'sector'              => 'archive',
            'sector_label'        => self::SECTOR_LABELS['archive'],
            'barcode_sources'     => $barcodeSources,
            'default_barcode_data' => $this->selectDefaultBarcodeData($barcodeSources),
            'repository_name'     => $repositoryName,
            'qr_url'              => url('/accession/' . $accession->id),
        ];
    }

    /**
     * Resolve the RiC-O entity type from properties.
     *
     * Heratio uses class_name (QubitInformationObject, QubitActor, etc.).
     * OpenRiC reads rdf:type from the triplestore graph.
     */
    private function resolveEntityType(array $properties): string
    {
        $types = $properties['rdf:type'] ?? [];
        if (is_string($types)) {
            $types = [$types];
        }

        foreach (self::RECORD_TYPES as $recordType) {
            if (in_array($recordType, $types, true)) {
                return 'RecordResource';
            }
        }

        foreach (self::AGENT_TYPES as $agentType) {
            if (in_array($agentType, $types, true)) {
                return 'Agent';
            }
        }

        // Default to RecordResource
        return 'RecordResource';
    }

    /**
     * Extract entity title from properties.
     *
     * Heratio reads from information_object_i18n.title or actor_i18n.authorized_form_of_name.
     * OpenRiC reads rico:title for records and rico:hasAgentName/rico:textualValue for agents.
     */
    private function extractTitle(array $properties, string $entityType): string
    {
        // Try rico:title first (works for records)
        if (!empty($properties['rico:title'])) {
            $title = is_array($properties['rico:title'])
                ? ($properties['rico:title'][0] ?? '')
                : $properties['rico:title'];
            return html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8');
        }

        // Try rico:hasAgentName for agents (equivalent to authorized_form_of_name)
        if ($entityType === 'Agent') {
            $agentName = $properties['rico:hasAgentName'] ?? null;
            if (is_array($agentName)) {
                // May be a nested structure: hasAgentName → textualValue
                $textual = $agentName['rico:textualValue'] ?? ($agentName[0] ?? '');
                return html_entity_decode((string) (is_array($textual) ? ($textual[0] ?? '') : $textual), ENT_QUOTES, 'UTF-8');
            }
            if (is_string($agentName) && $agentName !== '') {
                return html_entity_decode($agentName, ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallback: rdfs:label
        if (!empty($properties['rdfs:label'])) {
            $label = is_array($properties['rdfs:label'])
                ? ($properties['rdfs:label'][0] ?? '')
                : $properties['rdfs:label'];
            return html_entity_decode((string) $label, ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    /**
     * Extract the identifier from entity properties.
     *
     * Heratio reads information_object.identifier or actor.description_identifier.
     * OpenRiC reads rico:identifier from the triplestore.
     */
    private function extractIdentifier(array $properties): string
    {
        if (!empty($properties['rico:identifier'])) {
            $id = is_array($properties['rico:identifier'])
                ? ($properties['rico:identifier'][0] ?? '')
                : $properties['rico:identifier'];
            return (string) $id;
        }

        // Try reference code
        if (!empty($properties['rico:referenceCode'])) {
            return (string) (is_array($properties['rico:referenceCode'])
                ? ($properties['rico:referenceCode'][0] ?? '')
                : $properties['rico:referenceCode']);
        }

        return '';
    }

    /**
     * Extract barcode sources from entity properties.
     *
     * Adapted from Heratio LabelController::index() which checks:
     *   - information_object.identifier
     *   - library_item.isbn, issn, lccn, openlibrary_id, barcode, call_number
     *   - accession.identifier
     *   - title as last resort
     *
     * OpenRiC reads these from triplestore properties:
     *   - rico:identifier → identifier
     *   - schema:isbn → isbn
     *   - schema:issn → issn
     *   - bibo:lccn → lccn
     *   - schema:identifier (with type) → barcode / call_number
     *   - rico:title → title
     */
    public function extractBarcodeSources(array $properties, string $entityType, string $title, string $identifier): array
    {
        $barcodeSources = [];

        // 1. Identifier (always first, equivalent to Heratio's entity.identifier)
        if (!empty($identifier)) {
            $barcodeSources['identifier'] = [
                'label' => 'Identifier',
                'value' => $identifier,
            ];
        }

        // 2. Library-specific fields (ISBN, ISSN, LCCN, barcode, call number)
        //    Heratio reads from library_item table. OpenRiC stores these as RDF properties.
        if ($entityType === 'RecordResource') {
            $isbn = $this->extractProperty($properties, 'schema:isbn');
            if ($isbn !== '') {
                $barcodeSources['isbn'] = [
                    'label' => 'ISBN',
                    'value' => $isbn,
                ];
            }

            $issn = $this->extractProperty($properties, 'schema:issn');
            if ($issn !== '') {
                $barcodeSources['issn'] = [
                    'label' => 'ISSN',
                    'value' => $issn,
                ];
            }

            $lccn = $this->extractProperty($properties, 'bibo:lccn');
            if ($lccn !== '') {
                $barcodeSources['lccn'] = [
                    'label' => 'LCCN',
                    'value' => $lccn,
                ];
            }

            $barcode = $this->extractProperty($properties, 'schema:barcode');
            if ($barcode === '') {
                $barcode = $this->extractProperty($properties, 'openric:barcode');
            }
            if ($barcode !== '') {
                $barcodeSources['barcode'] = [
                    'label' => 'Barcode',
                    'value' => $barcode,
                ];
            }

            $callNumber = $this->extractProperty($properties, 'schema:callNumber');
            if ($callNumber === '') {
                $callNumber = $this->extractProperty($properties, 'openric:callNumber');
            }
            if ($callNumber !== '') {
                $barcodeSources['call_number'] = [
                    'label' => 'Call Number',
                    'value' => $callNumber,
                ];
            }

            // OpenLibrary ID — Heratio checks library_item.openlibrary_id
            $openlibraryId = $this->extractProperty($properties, 'openric:openlibraryId');
            if ($openlibraryId !== '') {
                $barcodeSources['openlibrary'] = [
                    'label' => 'OpenLibrary ID',
                    'value' => $openlibraryId,
                ];
            }
        }

        // 3. Title as last option (same as Heratio)
        if (!empty($title)) {
            $barcodeSources['title'] = [
                'label' => 'Title',
                'value' => $title,
            ];
        }

        return $barcodeSources;
    }

    /**
     * Extract a single string property from the properties array.
     */
    private function extractProperty(array $properties, string $key): string
    {
        if (!isset($properties[$key]) || $properties[$key] === '' || $properties[$key] === []) {
            return '';
        }

        $val = $properties[$key];
        if (is_array($val)) {
            return (string) ($val[0] ?? '');
        }

        return (string) $val;
    }

    /**
     * Select the preferred barcode data from barcode sources.
     *
     * Priority order from Heratio: isbn > issn > barcode > accession > identifier > title
     */
    public function selectDefaultBarcodeData(array $barcodeSources): string
    {
        foreach (self::BARCODE_PRIORITY as $key) {
            if (!empty($barcodeSources[$key]['value'])) {
                return $barcodeSources[$key]['value'];
            }
        }

        return '';
    }

    /**
     * Resolve the repository/holding institution name for a record entity.
     *
     * Adapted from Heratio which walks up the information_object hierarchy via parent_id
     * to find the first ancestor with a repository_id, then looks up actor_i18n for the name.
     * OpenRiC uses rico:heldBy / rico:isAssociatedWithCorporateBody from the triplestore,
     * or walks up rico:isOrWasIncludedIn hierarchy.
     */
    public function resolveRepositoryName(array $properties, string $entityType): string
    {
        if ($entityType !== 'RecordResource') {
            return '';
        }

        // Direct repository link: rico:heldBy
        $heldBy = $this->extractProperty($properties, 'rico:heldBy');
        if ($heldBy !== '') {
            return $this->resolveAgentName($heldBy);
        }

        // Try rico:isOrWasHeldBy (alternate predicate)
        $wasHeldBy = $this->extractProperty($properties, 'rico:isOrWasHeldBy');
        if ($wasHeldBy !== '') {
            return $this->resolveAgentName($wasHeldBy);
        }

        // Walk up hierarchy via rico:isOrWasIncludedIn (equivalent to Heratio's parent_id walk)
        $parentIri = $this->extractProperty($properties, 'rico:isOrWasIncludedIn');
        $maxDepth = 50;
        $visited = [];

        while ($parentIri !== '' && $maxDepth-- > 0) {
            // Prevent infinite loops
            if (in_array($parentIri, $visited, true)) {
                break;
            }
            $visited[] = $parentIri;

            $parentProps = $this->triplestore->getEntity($parentIri);
            if ($parentProps === null) {
                break;
            }

            $parentHeldBy = $this->extractProperty($parentProps, 'rico:heldBy');
            if ($parentHeldBy !== '') {
                return $this->resolveAgentName($parentHeldBy);
            }

            $parentWasHeldBy = $this->extractProperty($parentProps, 'rico:isOrWasHeldBy');
            if ($parentWasHeldBy !== '') {
                return $this->resolveAgentName($parentWasHeldBy);
            }

            $parentIri = $this->extractProperty($parentProps, 'rico:isOrWasIncludedIn');
        }

        return '';
    }

    /**
     * Resolve an agent name from its IRI.
     *
     * Heratio reads actor_i18n.authorized_form_of_name. OpenRiC reads rico:hasAgentName
     * or rico:title from the triplestore.
     */
    private function resolveAgentName(string $agentIri): string
    {
        $agentProps = $this->triplestore->getEntity($agentIri);
        if ($agentProps === null) {
            return '';
        }

        return $this->extractTitle($agentProps, 'Agent');
    }

    /**
     * Detect the sector for an entity.
     *
     * Heratio reads display_object_config.object_type for sector detection and checks
     * library_item existence for library sector. OpenRiC inspects RiC-O type properties
     * and library-specific properties (ISBN/ISSN presence).
     */
    public function detectSector(array $properties): string
    {
        // Check for explicit type annotation
        $explicitType = $this->extractProperty($properties, 'openric:sector');
        if ($explicitType !== '' && isset(self::SECTOR_LABELS[$explicitType])) {
            return $explicitType;
        }

        // Library detection: presence of ISBN or ISSN (same logic as Heratio)
        if ($this->extractProperty($properties, 'schema:isbn') !== ''
            || $this->extractProperty($properties, 'schema:issn') !== '') {
            return 'library';
        }

        // Museum detection: check for museum-related types
        $types = $properties['rdf:type'] ?? [];
        if (is_string($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            $typeLower = mb_strtolower((string) $type);
            if (str_contains($typeLower, 'museum') || str_contains($typeLower, 'physicalobject')) {
                return 'museum';
            }
            if (str_contains($typeLower, 'artwork') || str_contains($typeLower, 'gallery')) {
                return 'gallery';
            }
        }

        // Default sector
        return 'archive';
    }

    /**
     * Prepare label data for a batch of entities.
     *
     * Adapted from Heratio LabelController::batchPrint() which iterates slugs,
     * resolves each via slug → object → class_name chain. OpenRiC iterates IRIs
     * and resolves each via triplestore + accession table.
     */
    public function prepareBatchLabels(array $iris, ?string $barcodeSource = null): array
    {
        $labels = [];

        foreach ($iris as $iri) {
            $data = $this->resolveEntity($iri);
            if ($data === null) {
                continue;
            }

            // Override barcode data if a specific source is requested (same as Heratio)
            $barcodeData = $data['default_barcode_data'];
            if ($barcodeSource !== null && $barcodeSource === 'title') {
                $barcodeData = $data['title'];
            } elseif ($barcodeSource !== null && isset($data['barcode_sources'][$barcodeSource])) {
                $barcodeData = $data['barcode_sources'][$barcodeSource]['value'];
            }

            $labels[] = [
                'iri'         => $data['iri'],
                'title'       => $data['title'],
                'identifier'  => $data['identifier'],
                'barcodeData' => $barcodeData,
                'qrUrl'       => $data['qr_url'],
                'repository'  => $data['repository_name'],
            ];
        }

        return $labels;
    }
}
