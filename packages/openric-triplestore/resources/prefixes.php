<?php

declare(strict_types=1);

/**
 * Canonical RDF prefix definitions for OpenRiC SPARQL queries.
 *
 * These prefixes are prepended to every SPARQL query issued by the
 * TriplestoreService. Do not declare ad-hoc prefixes inline.
 */
return [
    'rico' => 'https://www.ica.org/standards/RiC/ontology#',
    'ricr' => 'https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#',
    'ricdft' => 'https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#',
    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
    'owl' => 'http://www.w3.org/2002/07/owl#',
    'xsd' => 'http://www.w3.org/2001/XMLSchema#',
    'dcterms' => 'http://purl.org/dc/terms/',
    'skos' => 'http://www.w3.org/2004/02/skos/core#',
    'prov' => 'http://www.w3.org/ns/prov#',
    'sh' => 'http://www.w3.org/ns/shacl#',
    'openric' => 'https://ric.theahg.co.za/ontology#',
    'entity' => 'https://ric.theahg.co.za/entity/',
    'user' => 'https://ric.theahg.co.za/user/',
];
