<?php

declare(strict_types=1);

namespace OpenRiC\DigitalObject\Services;

use OpenRiC\DigitalObject\Contracts\DigitalObjectServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Digital object service — adapted from Heratio DamService (874 lines).
 *
 * Heratio stores DAM assets across information_object, information_object_i18n,
 * dam_iptc_metadata, digital_object, display_object_config, and slug tables.
 * OpenRiC maps all this to rico:Instantiation entities in Fuseki.
 *
 * RiC-O property mapping for digital objects:
 *   rico:title              → title
 *   rico:identifier         → identifier
 *   rico:hasOrHadMimeType   → mimeType (e.g., "image/jpeg")
 *   rico:hasExtent          → fileSize in bytes
 *   rico:instantiates       → link to the Record IRI this instantiation represents
 *   rico:hasOrHadDigitalRepresentation → file storage path
 *   rico:descriptiveNote    → description/scope_and_content
 *   rico:date               → date created
 *   rico:hasCreator         → creator agent IRI
 *   dc:format               → format details
 *   rico:conditionsOfAccess → access restrictions
 *   rico:conditionsOfReproduction → license/rights info
 */
class DigitalObjectService implements DigitalObjectServiceInterface
{
    /**
     * RiC-O field map: form field → RiC-O property.
     */
    public const FIELD_MAP = [
        'title'             => ['property' => 'rico:title', 'datatype' => 'xsd:string'],
        'identifier'        => ['property' => 'rico:identifier', 'datatype' => 'xsd:string'],
        'description'       => ['property' => 'rico:descriptiveNote', 'datatype' => 'xsd:string'],
        'mime_type'         => ['property' => 'rico:hasOrHadMimeType', 'datatype' => 'xsd:string'],
        'file_size'         => ['property' => 'rico:hasExtent', 'datatype' => 'xsd:integer'],
        'file_path'         => ['property' => 'rico:hasOrHadDigitalRepresentation', 'datatype' => 'xsd:string'],
        'file_name'         => ['property' => 'rico:hasOrHadName', 'datatype' => 'xsd:string'],
        'date_created'      => ['property' => 'rico:date', 'datatype' => 'xsd:date'],
        'creator'           => ['property' => 'rico:hasCreator', 'datatype' => 'xsd:string'],
        'format'            => ['property' => 'dc:format', 'datatype' => 'xsd:string'],
        'access_conditions' => ['property' => 'rico:conditionsOfAccess', 'datatype' => 'xsd:string'],
        'license'           => ['property' => 'rico:conditionsOfReproduction', 'datatype' => 'xsd:string'],
        'checksum'          => ['property' => 'rico:integrity', 'datatype' => 'xsd:string'],
        'keywords'          => ['property' => 'rico:hasOrHadSubject', 'datatype' => 'xsd:string'],
        'record_iri'        => ['property' => 'rico:instantiates', 'type' => 'uri'],
    ];

    private TriplestoreServiceInterface $triplestore;
    private string $storageDisk;

    public function __construct(TriplestoreServiceInterface $triplestore, string $storageDisk = 'public')
    {
        $this->triplestore = $triplestore;
        $this->storageDisk = $storageDisk;
    }

    /**
     * Browse digital objects with filters, sorting, and pagination.
     *
     * Adapted from Heratio DamService::browse() which queries information_object
     * joined with dam_iptc_metadata. OpenRiC queries Fuseki for rico:Instantiation.
     */
    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $sort = $params['sort'] ?? 'lastUpdated';
        $sortDir = strtolower($params['sortDir'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $subquery = trim($params['subquery'] ?? '');
        $mimeType = trim($params['mimeType'] ?? '');
        $recordIri = trim($params['recordIri'] ?? '');

        $prefixes = $this->triplestore->getPrefixes();

        $filters = '';
        if ($subquery !== '') {
            $filters .= sprintf(
                '  FILTER(CONTAINS(LCASE(STR(?title)), LCASE("%s")) || CONTAINS(LCASE(STR(?identifier)), LCASE("%s")) || CONTAINS(LCASE(STR(?keywords)), LCASE("%s")))',
                addslashes($subquery),
                addslashes($subquery),
                addslashes($subquery)
            ) . "\n";
        }
        if ($mimeType !== '') {
            $filters .= sprintf('  FILTER(?mimeType = "%s")', addslashes($mimeType)) . "\n";
        }
        if ($recordIri !== '') {
            $filters .= sprintf('  ?iri rico:instantiates <%s> .', $recordIri) . "\n";
        }

        $orderBy = match ($sort) {
            'alphabetic' => "ORDER BY {$sortDir}(?title)",
            'identifier' => "ORDER BY {$sortDir}(?identifier)",
            'date'       => "ORDER BY {$sortDir}(?dateCreated)",
            default      => "ORDER BY {$sortDir}(?modified)",
        };

        $countSparql = $prefixes . '
SELECT (COUNT(DISTINCT ?iri) AS ?total)
WHERE {
  ?iri a rico:Instantiation .
  OPTIONAL { ?iri rico:title ?title . }
  OPTIONAL { ?iri rico:identifier ?identifier . }
  OPTIONAL { ?iri rico:hasOrHadSubject ?keywords . }
  OPTIONAL { ?iri rico:hasOrHadMimeType ?mimeType . }
' . $filters . '
}';

        $countResult = $this->triplestore->select($countSparql);
        $total = (int) ($countResult[0]['total'] ?? 0);

        $browseSparql = $prefixes . '
SELECT ?iri ?title ?identifier ?mimeType ?dateCreated ?modified ?creator ?keywords ?fileSize
WHERE {
  ?iri a rico:Instantiation .
  OPTIONAL { ?iri rico:title ?title . }
  OPTIONAL { ?iri rico:identifier ?identifier . }
  OPTIONAL { ?iri rico:hasOrHadMimeType ?mimeType . }
  OPTIONAL { ?iri rico:date ?dateCreated . }
  OPTIONAL { ?iri rico:hasModificationDate ?modified . }
  OPTIONAL { ?iri rico:hasCreator ?creator . }
  OPTIONAL { ?iri rico:hasOrHadSubject ?keywords . }
  OPTIONAL { ?iri rico:hasExtent ?fileSize . }
' . $filters . '
}
' . $orderBy . '
LIMIT ' . $limit . '
OFFSET ' . $offset;

        $rows = $this->triplestore->select($browseSparql);

        $hits = array_map(function (array $row): array {
            return [
                'iri'         => $row['iri'] ?? '',
                'title'       => $row['title'] ?? '',
                'identifier'  => $row['identifier'] ?? '',
                'mimeType'    => $row['mimeType'] ?? '',
                'dateCreated' => $row['dateCreated'] ?? '',
                'modified'    => $row['modified'] ?? '',
                'creator'     => $row['creator'] ?? '',
                'keywords'    => $row['keywords'] ?? '',
                'fileSize'    => (int) ($row['fileSize'] ?? 0),
            ];
        }, $rows);

        return [
            'hits'  => $hits,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Find a digital object by IRI.
     *
     * Adapted from Heratio DamService::getById() which joins information_object,
     * i18n, slug, and dam_iptc_metadata. OpenRiC gets all properties from Fuseki.
     */
    public function find(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return null;
        }

        $type = $entity['rdf:type'] ?? $entity['type'] ?? '';
        $types = is_array($type) ? $type : [$type];
        $isInstantiation = false;
        foreach ($types as $t) {
            if (str_contains((string) $t, 'Instantiation')) {
                $isInstantiation = true;
                break;
            }
        }
        if (!$isInstantiation) {
            return null;
        }

        return $this->mapEntityToArray($iri, $entity);
    }

    /**
     * Create a new digital object in the triplestore.
     *
     * Adapted from Heratio DamService::create() which inserts into
     * object, information_object, i18n, slug, display_object_config,
     * dam_iptc_metadata, and status tables (7 inserts in transaction).
     * OpenRiC creates a single rico:Instantiation entity.
     */
    public function create(array $data, string $userId): string
    {
        $properties = ['rdf:type' => 'rico:Instantiation'];

        foreach (self::FIELD_MAP as $field => $mapping) {
            if (!empty($data[$field])) {
                $properties[$mapping['property']] = $data[$field];
            }
        }

        $properties['rico:hasCreationDate'] = now()->toIso8601String();
        $properties['rico:hasModificationDate'] = now()->toIso8601String();

        $iri = $this->triplestore->createEntity(
            'Instantiation',
            $properties,
            $userId,
            'Created digital object'
        );

        // If linked to a record, create the relationship
        if (!empty($data['record_iri'])) {
            $this->triplestore->createRelationship(
                $iri,
                'rico:instantiates',
                $data['record_iri'],
                $userId,
                'Linked digital object to record'
            );
            // Create inverse relationship on the record
            $this->triplestore->createRelationship(
                $data['record_iri'],
                'rico:hasInstantiation',
                $iri,
                $userId,
                'Linked record to digital object'
            );
        }

        return $iri;
    }

    /**
     * Update an existing digital object.
     *
     * Adapted from Heratio DamService::update() which updates across
     * information_object, i18n, dam_iptc_metadata, and object tables.
     * OpenRiC updates properties on the rico:Instantiation entity.
     */
    public function update(string $iri, array $data, string $userId): void
    {
        $properties = [];

        foreach (self::FIELD_MAP as $field => $mapping) {
            if (array_key_exists($field, $data)) {
                $properties[$mapping['property']] = $data[$field];
            }
        }

        $properties['rico:hasModificationDate'] = now()->toIso8601String();

        $this->triplestore->updateEntity($iri, $properties, $userId, 'Updated digital object');

        // Update record link if changed
        if (array_key_exists('record_iri', $data)) {
            // Remove existing instantiates relationships
            $existing = $this->triplestore->getRelationships($iri);
            foreach ($existing as $rel) {
                if (($rel['predicate'] ?? '') === 'rico:instantiates') {
                    $this->triplestore->deleteRelationship(
                        $iri,
                        'rico:instantiates',
                        $rel['object'],
                        $userId,
                        'Removed old record link from digital object'
                    );
                    $this->triplestore->deleteRelationship(
                        $rel['object'],
                        'rico:hasInstantiation',
                        $iri,
                        $userId,
                        'Removed old digital object link from record'
                    );
                }
            }

            if (!empty($data['record_iri'])) {
                $this->triplestore->createRelationship(
                    $iri,
                    'rico:instantiates',
                    $data['record_iri'],
                    $userId,
                    'Linked digital object to record'
                );
                $this->triplestore->createRelationship(
                    $data['record_iri'],
                    'rico:hasInstantiation',
                    $iri,
                    $userId,
                    'Linked record to digital object'
                );
            }
        }
    }

    /**
     * Delete a digital object and its file.
     *
     * Adapted from Heratio DamService::delete() which deletes from
     * dam_iptc_metadata, display_object_config, dam_version_links,
     * dam_format_holdings, dam_external_links, status, i18n,
     * information_object, slug, and object (10 deletes in transaction).
     * OpenRiC deletes the entity and its file.
     */
    public function delete(string $iri, string $userId): void
    {
        // Get file path before deleting the entity
        $entity = $this->triplestore->getEntity($iri);
        $filePath = $entity['rico:hasOrHadDigitalRepresentation'] ?? null;

        // Remove record links
        $relationships = $this->triplestore->getRelationships($iri);
        foreach ($relationships as $rel) {
            $predicate = $rel['predicate'] ?? '';
            if ($predicate === 'rico:instantiates') {
                $this->triplestore->deleteRelationship(
                    $rel['object'],
                    'rico:hasInstantiation',
                    $iri,
                    $userId,
                    'Removed digital object link from record (digital object deleted)'
                );
            }
        }

        // Delete the entity from triplestore
        $this->triplestore->deleteEntity($iri, $userId, 'Deleted digital object');

        // Delete the physical file
        if ($filePath !== null && is_string($filePath) && $filePath !== '') {
            Storage::disk($this->storageDisk)->delete($filePath);
        }
    }

    /**
     * Get dashboard statistics.
     *
     * Adapted from Heratio DamService::getDashboardStats() which counts
     * from display_object_config, digital_object, dam_iptc_metadata with joins.
     * OpenRiC queries Fuseki for rico:Instantiation aggregates.
     */
    public function getDashboardStats(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        // Total digital objects
        $totalSparql = $prefixes . '
SELECT (COUNT(DISTINCT ?iri) AS ?total)
WHERE {
  ?iri a rico:Instantiation .
}';
        $totalResult = $this->triplestore->select($totalSparql);
        $totalObjects = (int) ($totalResult[0]['total'] ?? 0);

        // Objects with files
        $withFilesSparql = $prefixes . '
SELECT (COUNT(DISTINCT ?iri) AS ?total)
WHERE {
  ?iri a rico:Instantiation .
  ?iri rico:hasOrHadDigitalRepresentation ?path .
  FILTER(?path != "")
}';
        $withFilesResult = $this->triplestore->select($withFilesSparql);
        $withFiles = (int) ($withFilesResult[0]['total'] ?? 0);

        // By MIME type
        $byMimeSparql = $prefixes . '
SELECT ?mimeType (COUNT(DISTINCT ?iri) AS ?count)
WHERE {
  ?iri a rico:Instantiation .
  ?iri rico:hasOrHadMimeType ?mimeType .
  FILTER(?mimeType != "")
}
GROUP BY ?mimeType
ORDER BY DESC(?count)
LIMIT 50';
        $byMimeResult = $this->triplestore->select($byMimeSparql);
        $byMimeType = array_map(function (array $row): array {
            return [
                'mimeType' => $row['mimeType'] ?? '',
                'count'    => (int) ($row['count'] ?? 0),
            ];
        }, $byMimeResult);

        // Total file size
        $sizeSparql = $prefixes . '
SELECT (SUM(xsd:integer(?size)) AS ?totalSize)
WHERE {
  ?iri a rico:Instantiation .
  ?iri rico:hasExtent ?size .
}';
        $sizeResult = $this->triplestore->select($sizeSparql);
        $totalSizeBytes = (int) ($sizeResult[0]['totalSize'] ?? 0);

        return [
            'totalObjects'   => $totalObjects,
            'withFiles'      => $withFiles,
            'byMimeType'     => $byMimeType,
            'totalSizeBytes' => $totalSizeBytes,
        ];
    }

    /**
     * Get recently created digital objects.
     *
     * Adapted from Heratio DamService::getRecentAssets() which selects from
     * information_object joined with display_object_config, i18n, slug, iptc.
     */
    public function getRecentAssets(int $limit = 10): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
SELECT ?iri ?title ?identifier ?mimeType ?creator ?dateCreated
WHERE {
  ?iri a rico:Instantiation .
  OPTIONAL { ?iri rico:title ?title . }
  OPTIONAL { ?iri rico:identifier ?identifier . }
  OPTIONAL { ?iri rico:hasOrHadMimeType ?mimeType . }
  OPTIONAL { ?iri rico:hasCreator ?creator . }
  OPTIONAL { ?iri rico:hasCreationDate ?dateCreated . }
}
ORDER BY DESC(?dateCreated)
LIMIT ' . max(1, min(100, $limit));

        return array_map(function (array $row): array {
            return [
                'iri'         => $row['iri'] ?? '',
                'title'       => $row['title'] ?? '',
                'identifier'  => $row['identifier'] ?? '',
                'mimeType'    => $row['mimeType'] ?? '',
                'creator'     => $row['creator'] ?? '',
                'dateCreated' => $row['dateCreated'] ?? '',
            ];
        }, $this->triplestore->select($sparql));
    }

    /**
     * Get all digital objects linked to a record IRI.
     *
     * Adapted from Heratio DamService::getDigitalObjects() which queries
     * digital_object WHERE object_id = ? AND parent_id IS NULL.
     * OpenRiC follows the rico:instantiates relationship.
     */
    public function getDigitalObjectsForRecord(string $recordIri): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
SELECT ?iri ?title ?identifier ?mimeType ?fileSize ?filePath ?fileName
WHERE {
  ?iri a rico:Instantiation .
  ?iri rico:instantiates <' . $recordIri . '> .
  OPTIONAL { ?iri rico:title ?title . }
  OPTIONAL { ?iri rico:identifier ?identifier . }
  OPTIONAL { ?iri rico:hasOrHadMimeType ?mimeType . }
  OPTIONAL { ?iri rico:hasExtent ?fileSize . }
  OPTIONAL { ?iri rico:hasOrHadDigitalRepresentation ?filePath . }
  OPTIONAL { ?iri rico:hasOrHadName ?fileName . }
}
ORDER BY ?title
LIMIT 500';

        return array_map(function (array $row): array {
            return [
                'iri'       => $row['iri'] ?? '',
                'title'     => $row['title'] ?? '',
                'identifier' => $row['identifier'] ?? '',
                'mimeType'  => $row['mimeType'] ?? '',
                'fileSize'  => (int) ($row['fileSize'] ?? 0),
                'filePath'  => $row['filePath'] ?? '',
                'fileName'  => $row['fileName'] ?? '',
            ];
        }, $this->triplestore->select($sparql));
    }

    /**
     * Upload a file and associate it with a digital object.
     *
     * Heratio stores file data in the digital_object table (mime_type, byte_size,
     * name, path). OpenRiC stores file metadata as RDF properties on the
     * rico:Instantiation and the actual file on disk via Storage facade.
     */
    public function uploadFile(string $iri, UploadedFile $file, string $userId): array
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $sizeBytes = $file->getSize();
        $checksum = hash_file('sha256', $file->getRealPath());

        // Store the file using a path based on the IRI hash
        $iriHash = substr(md5($iri), 0, 12);
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $storagePath = 'digital-objects/' . $iriHash . '/' . $originalName;

        Storage::disk($this->storageDisk)->putFileAs(
            'digital-objects/' . $iriHash,
            $file,
            $originalName
        );

        // Update the triplestore entity with file metadata
        $this->triplestore->updateEntity($iri, [
            'rico:hasOrHadDigitalRepresentation' => $storagePath,
            'rico:hasOrHadMimeType'              => $mimeType,
            'rico:hasExtent'                     => (string) $sizeBytes,
            'rico:hasOrHadName'                  => $originalName,
            'rico:integrity'                     => 'sha256:' . $checksum,
            'rico:hasModificationDate'           => now()->toIso8601String(),
        ], $userId, 'Uploaded file for digital object');

        return [
            'path'      => $storagePath,
            'mimeType'  => $mimeType,
            'sizeBytes' => (int) $sizeBytes,
            'filename'  => $originalName,
        ];
    }

    /**
     * Delete the file associated with a digital object.
     */
    public function deleteFile(string $iri, string $userId): void
    {
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return;
        }

        $filePath = $entity['rico:hasOrHadDigitalRepresentation'] ?? null;
        if ($filePath !== null && is_string($filePath) && $filePath !== '') {
            Storage::disk($this->storageDisk)->delete($filePath);
        }

        // Clear file-related properties
        $this->triplestore->updateEntity($iri, [
            'rico:hasOrHadDigitalRepresentation' => '',
            'rico:hasOrHadMimeType'              => '',
            'rico:hasExtent'                     => '',
            'rico:hasOrHadName'                  => '',
            'rico:integrity'                     => '',
            'rico:hasModificationDate'           => now()->toIso8601String(),
        ], $userId, 'Deleted file from digital object');
    }

    /**
     * Get file metadata for a digital object.
     */
    public function getFileMetadata(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return null;
        }

        $filePath = $entity['rico:hasOrHadDigitalRepresentation'] ?? '';
        if ($filePath === '' || !is_string($filePath)) {
            return null;
        }

        return [
            'path'      => $filePath,
            'mimeType'  => $entity['rico:hasOrHadMimeType'] ?? '',
            'sizeBytes' => (int) ($entity['rico:hasExtent'] ?? 0),
            'filename'  => $entity['rico:hasOrHadName'] ?? '',
        ];
    }

    /**
     * Map a raw triplestore entity to an array.
     */
    private function mapEntityToArray(string $iri, array $entity): array
    {
        $result = ['iri' => $iri];

        foreach (self::FIELD_MAP as $field => $mapping) {
            $property = $mapping['property'];
            $result[$field] = $entity[$property] ?? null;
        }

        $result['created_at'] = $entity['rico:hasCreationDate'] ?? null;
        $result['updated_at'] = $entity['rico:hasModificationDate'] ?? null;

        return $result;
    }
}
