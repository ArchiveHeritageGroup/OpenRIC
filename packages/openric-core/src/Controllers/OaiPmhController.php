<?php

declare(strict_types=1);

namespace OpenRiC\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenRiC\Core\Contracts\OaiPmhServiceInterface;

/**
 * OAI-PMH 2.0 endpoint controller.
 *
 * Implements the six OAI-PMH verbs: Identify, ListMetadataFormats,
 * ListSets, ListIdentifiers, ListRecords, GetRecord.
 *
 * All data retrieval is delegated to OaiPmhServiceInterface.
 * Responses are XML per the OAI-PMH 2.0 specification.
 */
class OaiPmhController extends Controller
{
    /**
     * OAI-PMH error messages keyed by error code.
     *
     * @var array<string, string>
     */
    private const ERRORS = [
        'badArgument'              => 'The request includes illegal arguments, is missing required arguments, includes a repeated argument, or values for arguments have an illegal syntax.',
        'badResumptionToken'       => 'The value of the resumptionToken argument is invalid or expired.',
        'badVerb'                  => 'Value of the verb argument is not a legal OAI-PMH verb, the verb argument is missing, or the verb argument is repeated.',
        'cannotDisseminateFormat'  => 'The metadata format identified by the value given for the metadataPrefix argument is not supported by the item or by the repository.',
        'idDoesNotExist'           => 'The value of the identifier argument is unknown or illegal in this repository.',
        'noRecordsMatch'           => 'The combination of the values of the from, until, set and metadataPrefix arguments results in an empty list.',
        'noMetadataFormats'        => 'There are no metadata formats available for the specified item.',
        'noSetHierarchy'           => 'The repository does not support sets.',
    ];

    /**
     * Valid OAI-PMH verbs.
     *
     * @var array<int, string>
     */
    private const VERBS = [
        'Identify', 'ListMetadataFormats', 'ListSets',
        'ListIdentifiers', 'ListRecords', 'GetRecord',
    ];

    /**
     * Allowed and mandatory parameters per verb.
     *
     * @var array<string, array{allowed: array<int, string>, mandatory: array<int, string>}>
     */
    private const VERB_PARAMS = [
        'Identify'            => ['allowed' => ['verb'], 'mandatory' => ['verb']],
        'ListMetadataFormats' => ['allowed' => ['verb', 'identifier'], 'mandatory' => ['verb']],
        'ListSets'            => ['allowed' => ['verb', 'resumptionToken'], 'mandatory' => ['verb']],
        'ListIdentifiers'     => ['allowed' => ['verb', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'], 'mandatory' => ['verb', 'metadataPrefix']],
        'ListRecords'         => ['allowed' => ['verb', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'], 'mandatory' => ['verb', 'metadataPrefix']],
        'GetRecord'           => ['allowed' => ['verb', 'identifier', 'metadataPrefix'], 'mandatory' => ['verb', 'identifier', 'metadataPrefix']],
    ];

    /**
     * Page size for resumption tokens.
     */
    private const PAGE_SIZE = 100;

    public function __construct(
        private readonly OaiPmhServiceInterface $oaiService,
    ) {}

    /**
     * Get the repository identifier (domain name).
     */
    private function getRepositoryIdentifier(): string
    {
        return request()->getHost();
    }

    /**
     * Format an OAI identifier from an entity IRI.
     *
     * Encodes the IRI for safe inclusion in the OAI identifier.
     */
    private function formatOaiIdentifier(string $iri): string
    {
        return 'oai:' . $this->getRepositoryIdentifier() . ':' . urlencode($iri);
    }

    /**
     * Parse an entity IRI from an OAI identifier string.
     */
    private function parseOaiIdentifier(string $identifier): ?string
    {
        $prefix = 'oai:' . $this->getRepositoryIdentifier() . ':';

        if (!str_starts_with($identifier, $prefix)) {
            return null;
        }

        $encoded = substr($identifier, strlen($prefix));

        if ($encoded === '' || $encoded === false) {
            return null;
        }

        return urldecode($encoded);
    }

    /**
     * Get ISO 8601 UTC date string.
     */
    private function getDate(?string $date = null): string
    {
        if ($date === null || $date === '') {
            return gmdate('Y-m-d\TH:i:s\Z');
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    /**
     * Main dispatcher: reads `verb` parameter and routes to the correct method.
     *
     * Supports both GET and POST per OAI-PMH 2.0 specification.
     */
    public function handle(Request $request): Response
    {
        $verb = $request->input('verb');

        if (empty($verb) || !in_array($verb, self::VERBS, true)) {
            return $this->errorResponse($request, 'badVerb');
        }

        $params = $request->all();

        if (isset($params['resumptionToken'])) {
            $decoded = $this->decodeResumptionToken($params['resumptionToken']);

            if ($decoded === false) {
                return $this->errorResponse($request, 'badResumptionToken');
            }

            $params = array_merge($params, $decoded);

            $inputKeys = array_keys($request->all());
            $nonTokenKeys = array_diff($inputKeys, ['verb', 'resumptionToken']);

            if (count($nonTokenKeys) > 0) {
                return $this->errorResponse($request, 'badArgument');
            }
        } else {
            $inputKeys = array_keys($request->all());
            $verbConfig = self::VERB_PARAMS[$verb];

            foreach ($inputKeys as $key) {
                if (!in_array($key, $verbConfig['allowed'], true)) {
                    return $this->errorResponse($request, 'badArgument');
                }
            }

            foreach ($verbConfig['mandatory'] as $mandatoryKey) {
                if (!in_array($mandatoryKey, $inputKeys, true)) {
                    return $this->errorResponse($request, 'badArgument');
                }
            }
        }

        $metadataPrefix = $params['metadataPrefix'] ?? null;

        if ($metadataPrefix !== null && $metadataPrefix !== '' && $metadataPrefix !== 'oai_dc') {
            return $this->errorResponse($request, 'cannotDisseminateFormat');
        }

        foreach (['from', 'until'] as $dateParam) {
            if (isset($params[$dateParam]) && $params[$dateParam] !== '' && !$this->isValidDate($params[$dateParam])) {
                return $this->errorResponse($request, 'badArgument');
            }
        }

        return match ($verb) {
            'Identify'            => $this->identify($request),
            'ListMetadataFormats' => $this->listMetadataFormats($request),
            'ListSets'            => $this->listSets($request, $params),
            'ListIdentifiers'     => $this->listIdentifiers($request, $params),
            'ListRecords'         => $this->listRecords($request, $params),
            'GetRecord'           => $this->getRecord($request, $params),
            default               => $this->errorResponse($request, 'badVerb'),
        };
    }

    /**
     * Identify verb: repository identification.
     */
    private function identify(Request $request): Response
    {
        $earliestDatestamp = $this->oaiService->getEarliestDatestamp();
        $repositoryName = config('openric-core.oai.repository_name', 'OpenRiC');
        $baseUrl = $request->url();
        $adminEmail = config('openric-core.oai.admin_email', 'admin@' . $request->getHost());

        $xml = $this->xmlHeader($request);
        $xml .= '  <Identify>' . "\n";
        $xml .= '    <repositoryName>' . $this->esc($repositoryName) . '</repositoryName>' . "\n";
        $xml .= '    <baseURL>' . $this->esc($baseUrl) . '</baseURL>' . "\n";
        $xml .= '    <protocolVersion>2.0</protocolVersion>' . "\n";
        $xml .= '    <adminEmail>' . $this->esc($adminEmail) . '</adminEmail>' . "\n";
        $xml .= '    <earliestDatestamp>' . $earliestDatestamp . '</earliestDatestamp>' . "\n";
        $xml .= '    <deletedRecord>no</deletedRecord>' . "\n";
        $xml .= '    <granularity>YYYY-MM-DDThh:mm:ssZ</granularity>' . "\n";
        $xml .= '    <description>' . "\n";
        $xml .= '      <oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier"' . "\n";
        $xml .= '                      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '                      xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">' . "\n";
        $xml .= '        <scheme>oai</scheme>' . "\n";
        $xml .= '        <repositoryIdentifier>' . $this->esc($this->getRepositoryIdentifier()) . '</repositoryIdentifier>' . "\n";
        $xml .= '        <delimiter>:</delimiter>' . "\n";
        $xml .= '        <sampleIdentifier>oai:' . $this->esc($this->getRepositoryIdentifier()) . ':sample-iri</sampleIdentifier>' . "\n";
        $xml .= '      </oai-identifier>' . "\n";
        $xml .= '    </description>' . "\n";
        $xml .= '  </Identify>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * ListMetadataFormats verb: return supported metadata formats.
     */
    private function listMetadataFormats(Request $request): Response
    {
        $xml = $this->xmlHeader($request);
        $xml .= '  <ListMetadataFormats>' . "\n";
        $xml .= '    <metadataFormat>' . "\n";
        $xml .= '      <metadataPrefix>oai_dc</metadataPrefix>' . "\n";
        $xml .= '      <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>' . "\n";
        $xml .= '      <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>' . "\n";
        $xml .= '    </metadataFormat>' . "\n";
        $xml .= '  </ListMetadataFormats>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * ListSets verb: return available sets (top-level fonds).
     */
    private function listSets(Request $request, array $params): Response
    {
        $cursor = (int) ($params['cursor'] ?? 0);

        $result = $this->oaiService->getSets($cursor, self::PAGE_SIZE);
        $sets = $result['items'];
        $totalCount = $result['total'];

        if (empty($sets) && $cursor === 0) {
            return $this->errorResponse($request, 'noSetHierarchy');
        }

        $xml = $this->xmlHeader($request);
        $xml .= '  <ListSets>' . "\n";

        foreach ($sets as $set) {
            $xml .= '    <set>' . "\n";
            $xml .= '      <setSpec>' . $this->esc($this->formatOaiIdentifier($set['iri'])) . '</setSpec>' . "\n";
            $xml .= '      <setName>' . $this->esc($set['title']) . '</setName>' . "\n";
            $xml .= '    </set>' . "\n";
        }

        $remaining = $totalCount - $cursor - count($sets);

        if ($remaining > 0) {
            $token = $this->encodeResumptionToken([
                'cursor' => $cursor + self::PAGE_SIZE,
            ]);
            $xml .= '    <resumptionToken>' . $token . '</resumptionToken>' . "\n";
        }

        $xml .= '  </ListSets>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * ListIdentifiers verb: list record identifiers with datestamps.
     */
    private function listIdentifiers(Request $request, array $params): Response
    {
        $cursor = (int) ($params['cursor'] ?? 0);
        $from = $params['from'] ?? null;
        $until = $params['until'] ?? null;
        $set = $params['set'] ?? null;
        $metadataPrefix = $params['metadataPrefix'] ?? 'oai_dc';

        $setIri = null;

        if ($set !== null && $set !== '') {
            $setIri = $this->parseOaiIdentifier($set);

            if ($setIri === null) {
                $setIri = $set;
            }
        }

        $result = $this->oaiService->getRecordHeaders($from, $until, $setIri, $cursor, self::PAGE_SIZE);
        $records = $result['items'];
        $totalCount = $result['total'];

        if (empty($records)) {
            return $this->errorResponse($request, 'noRecordsMatch');
        }

        $xml = $this->xmlHeader($request);
        $xml .= '  <ListIdentifiers>' . "\n";

        foreach ($records as $record) {
            $xml .= $this->renderHeader($record);
        }

        $remaining = $totalCount - $cursor - count($records);

        if ($remaining > 0) {
            $token = $this->encodeResumptionToken([
                'cursor'         => $cursor + self::PAGE_SIZE,
                'metadataPrefix' => $metadataPrefix,
                'from'           => $from ?? '',
                'until'          => $until ?? '',
                'set'            => $set ?? '',
            ]);
            $xml .= '    <resumptionToken>' . $token . '</resumptionToken>' . "\n";
        }

        $xml .= '  </ListIdentifiers>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * ListRecords verb: list full records with DC metadata.
     */
    private function listRecords(Request $request, array $params): Response
    {
        $cursor = (int) ($params['cursor'] ?? 0);
        $from = $params['from'] ?? null;
        $until = $params['until'] ?? null;
        $set = $params['set'] ?? null;
        $metadataPrefix = $params['metadataPrefix'] ?? 'oai_dc';

        $setIri = null;

        if ($set !== null && $set !== '') {
            $setIri = $this->parseOaiIdentifier($set);

            if ($setIri === null) {
                $setIri = $set;
            }
        }

        $result = $this->oaiService->getRecords($from, $until, $setIri, $cursor, self::PAGE_SIZE);
        $records = $result['items'];
        $totalCount = $result['total'];

        if (empty($records)) {
            return $this->errorResponse($request, 'noRecordsMatch');
        }

        $xml = $this->xmlHeader($request);
        $xml .= '  <ListRecords>' . "\n";

        foreach ($records as $record) {
            $dc = $this->oaiService->mapToDublinCore($record);

            $xml .= '    <record>' . "\n";
            $xml .= $this->renderHeader($record, 6);
            $xml .= '      <metadata>' . "\n";
            $xml .= $this->renderDublinCore($dc);
            $xml .= '      </metadata>' . "\n";
            $xml .= '    </record>' . "\n";
        }

        $remaining = $totalCount - $cursor - count($records);

        if ($remaining > 0) {
            $token = $this->encodeResumptionToken([
                'cursor'         => $cursor + self::PAGE_SIZE,
                'metadataPrefix' => $metadataPrefix,
                'from'           => $from ?? '',
                'until'          => $until ?? '',
                'set'            => $set ?? '',
            ]);
            $xml .= '    <resumptionToken>' . $token . '</resumptionToken>' . "\n";
        }

        $xml .= '  </ListRecords>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * GetRecord verb: retrieve a single record by OAI identifier.
     */
    private function getRecord(Request $request, array $params): Response
    {
        $identifier = $params['identifier'] ?? '';

        $iri = $this->parseOaiIdentifier($identifier);

        if ($iri === null) {
            return $this->errorResponse($request, 'idDoesNotExist');
        }

        $record = $this->oaiService->getRecord($iri);

        if ($record === null) {
            return $this->errorResponse($request, 'idDoesNotExist');
        }

        $dc = $this->oaiService->mapToDublinCore($record);

        $xml = $this->xmlHeader($request);
        $xml .= '  <GetRecord>' . "\n";
        $xml .= '    <record>' . "\n";
        $xml .= $this->renderHeader($record, 6);
        $xml .= '      <metadata>' . "\n";
        $xml .= $this->renderDublinCore($dc);
        $xml .= '      </metadata>' . "\n";
        $xml .= '    </record>' . "\n";
        $xml .= '  </GetRecord>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Render a <header> element for a record.
     *
     * @param  array<string, mixed> $record  record data with 'iri', 'datestamp', 'setIri' keys
     * @param  int                  $indent  number of spaces for indentation
     * @return string  XML header element
     */
    private function renderHeader(array $record, int $indent = 4): string
    {
        $pad = str_repeat(' ', $indent);
        $xml = $pad . '<header>' . "\n";
        $xml .= $pad . '  <identifier>' . $this->esc($this->formatOaiIdentifier($record['iri'])) . '</identifier>' . "\n";
        $xml .= $pad . '  <datestamp>' . $this->esc($record['datestamp'] ?? $this->getDate()) . '</datestamp>' . "\n";

        $setIri = $record['setIri'] ?? null;

        if ($setIri === null && isset($record['iri'])) {
            $setIri = $this->oaiService->getSetForRecord($record['iri']);
        }

        if ($setIri !== null) {
            $xml .= $pad . '  <setSpec>' . $this->esc($this->formatOaiIdentifier($setIri)) . '</setSpec>' . "\n";
        }

        $xml .= $pad . '</header>' . "\n";

        return $xml;
    }

    /**
     * Render Dublin Core metadata XML from a DC element map.
     *
     * @param  array<string, array<int, string>> $dc  DC element name => array of values
     * @return string  oai_dc:dc XML block
     */
    private function renderDublinCore(array $dc): string
    {
        $xml = '        <oai_dc:dc' . "\n";
        $xml .= '            xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"' . "\n";
        $xml .= '            xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n";
        $xml .= '            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '            xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/' . "\n";
        $xml .= '            http://www.openarchives.org/OAI/2.0/oai_dc.xsd">' . "\n";

        foreach ($dc as $element => $values) {
            foreach ($values as $value) {
                if ($value !== '') {
                    $xml .= '          <' . $element . '>' . $this->esc($value) . '</' . $element . '>' . "\n";
                }
            }
        }

        $xml .= '        </oai_dc:dc>' . "\n";

        return $xml;
    }

    /**
     * Encode a resumption token from an associative array.
     *
     * @param  array<string, mixed> $data  token data
     * @return string  base64-encoded JSON token
     */
    private function encodeResumptionToken(array $data): string
    {
        return base64_encode(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Decode a resumption token. Returns false on failure.
     *
     * @param  string $token  base64-encoded JSON token
     * @return array<string, mixed>|false  decoded data or false
     */
    private function decodeResumptionToken(string $token): array|false
    {
        $json = base64_decode($token, true);

        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return false;
        }

        return $data;
    }

    /**
     * Validate an ISO 8601 date string (YYYY-MM-DD or YYYY-MM-DDThh:mm:ssZ).
     *
     * @param  string $date  date string to validate
     * @return bool  true if valid
     */
    private function isValidDate(string $date): bool
    {
        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return false;
        }

        $tPos = strpos($parts[2], 'T');

        if ($tPos !== false) {
            $time = substr($parts[2], $tPos);
            $parts[2] = substr($parts[2], 0, $tPos);

            if (!preg_match('/^T(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]Z$/i', $time)) {
                return false;
            }
        }

        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return false;
        }

        return true;
    }

    /**
     * Escape special XML characters.
     *
     * @param  string $value  raw value
     * @return string  XML-safe string
     */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate the OAI-PMH XML envelope header.
     *
     * @param  Request $request  current HTTP request
     * @return string  XML header including responseDate and request element
     */
    private function xmlHeader(Request $request): string
    {
        $date = $this->getDate();
        $requestUrl = $request->url();

        $attrs = '';

        foreach ($request->all() as $key => $value) {
            $attrs .= ' ' . $key . '="' . $this->esc((string) $value) . '"';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"' . "\n";
        $xml .= '         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">' . "\n";
        $xml .= '  <responseDate>' . $date . '</responseDate>' . "\n";
        $xml .= '  <request' . $attrs . '>' . $this->esc($requestUrl) . '</request>' . "\n";

        return $xml;
    }

    /**
     * Generate the OAI-PMH XML envelope footer.
     *
     * @return string  closing OAI-PMH tag
     */
    private function xmlFooter(): string
    {
        return '</OAI-PMH>' . "\n";
    }

    /**
     * Build and return an XML error response.
     *
     * @param  Request $request    current HTTP request
     * @param  string  $errorCode  OAI-PMH error code
     * @return Response  XML error response
     */
    private function errorResponse(Request $request, string $errorCode): Response
    {
        $errorMsg = self::ERRORS[$errorCode] ?? 'Unknown error.';

        $xml = $this->xmlHeader($request);
        $xml .= '  <error code="' . $errorCode . '">' . $this->esc($errorMsg) . '</error>' . "\n";
        $xml .= $this->xmlFooter();

        return $this->xmlResponse($xml);
    }

    /**
     * Return an XML response with the correct content type.
     *
     * @param  string $xml  XML content
     * @return Response  HTTP response with text/xml content type
     */
    private function xmlResponse(string $xml): Response
    {
        return new Response($xml, 200, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }
}
