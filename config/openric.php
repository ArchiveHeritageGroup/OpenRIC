<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Base URI
    |--------------------------------------------------------------------------
    |
    | The base URI for all RiC-O entities created by OpenRiC.
    | Entity IRIs follow the pattern: {base_uri}/{type}/{uuid}
    |
    */
    'base_uri' => env('OPENRIC_BASE_URI', 'https://ric.theahg.co.za/entity'),

    /*
    |--------------------------------------------------------------------------
    | User Base URI
    |--------------------------------------------------------------------------
    |
    | Used for RDF-Star provenance annotations (who made the change).
    |
    */
    'user_base_uri' => env('OPENRIC_USER_BASE_URI', 'https://ric.theahg.co.za/user'),

    /*
    |--------------------------------------------------------------------------
    | Ontology Namespace
    |--------------------------------------------------------------------------
    |
    | Custom OpenRiC ontology namespace for provenance predicates.
    |
    */
    'ontology_uri' => env('OPENRIC_ONTOLOGY_URI', 'https://ric.theahg.co.za/ontology#'),

    /*
    |--------------------------------------------------------------------------
    | Default View Mode
    |--------------------------------------------------------------------------
    |
    | Which view to show by default: 'ric' or 'traditional'.
    | Users can toggle per-session.
    |
    */
    'default_view' => env('OPENRIC_DEFAULT_VIEW', 'ric'),

    /*
    |--------------------------------------------------------------------------
    | Description Provenance Model
    |--------------------------------------------------------------------------
    |
    | Per RiC-CM 1.0 section 6 and ICA/EGAD guidance (Florence Clavaud,
    | 2026-03-27), OpenRiC implements dual provenance:
    |
    | 1. RDF-Star annotations on triple writes (lightweight: who/when/why)
    | 2. Description-as-Record model: a rico:Record with
    |    rico:describesOrDescribed linking to the described entity,
    |    with rico:hasDocumentaryFormType FindingAid or AuthorityRecord.
    |
    | See: https://ica-egad.github.io/RiC-AG/faq--general_questions_and_smaller_modelling_questions.html#do-you-have-examples-illustrating-chapter-6-of-ric-cm
    |
    */
    'provenance' => [
        'rdf_star_enabled' => true,
        'description_as_record_enabled' => true,
        'documentary_form_types' => [
            'finding_aid' => 'https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#FindingAid',
            'authority_record' => 'https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#AuthorityRecord',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | License
    |--------------------------------------------------------------------------
    */
    'license' => 'AGPL-3.0-or-later',

    /*
    |--------------------------------------------------------------------------
    | ISAD(G) to RiC-O Mappings
    |--------------------------------------------------------------------------
    |
    | Maps ISAD(G) elements to RiC-O properties. Used by the traditional
    | lens to render ISAD(G) views from RiC-O triples and to convert
    | ISAD(G) form input into RiC-O triples on submission.
    |
    */
    'mappings' => [
        'isadg' => [
            // 3.1 Identity Statement Area
            '3.1.1' => ['label' => 'Reference code(s)', 'rico_property' => 'rico:identifier', 'rico_class' => null, 'required' => false],
            '3.1.2' => ['label' => 'Title', 'rico_property' => 'rico:title', 'rico_class' => null, 'required' => true],
            '3.1.3' => ['label' => 'Date(s)', 'rico_property' => 'rico:isAssociatedWithDate', 'rico_class' => 'rico:DateRange', 'required' => false],
            '3.1.4' => ['label' => 'Level of description', 'rico_property' => 'rdf:type', 'rico_class' => null, 'required' => true],
            '3.1.5' => ['label' => 'Extent and medium of the unit of description', 'rico_property' => 'rico:carrierExtent', 'rico_class' => 'rico:Instantiation', 'required' => false],

            // 3.2 Context Area
            '3.2.1' => ['label' => 'Name of creator(s)', 'rico_property' => 'rico:hasOrHadCreator', 'rico_class' => 'rico:Agent', 'required' => false],
            '3.2.2' => ['label' => 'Administrative/biographical history', 'rico_property' => 'rico:history', 'rico_class' => null, 'required' => false],
            '3.2.3' => ['label' => 'Archival history', 'rico_property' => 'rico:history', 'rico_class' => null, 'required' => false],
            '3.2.4' => ['label' => 'Immediate source of acquisition or transfer', 'rico_property' => 'rico:history', 'rico_class' => null, 'required' => false],

            // 3.3 Content and Structure Area
            '3.3.1' => ['label' => 'Scope and content', 'rico_property' => 'rico:scopeAndContent', 'rico_class' => null, 'required' => false],
            '3.3.2' => ['label' => 'Appraisal, destruction and scheduling information', 'rico_property' => 'rico:conditionsOfAccess', 'rico_class' => null, 'required' => false],
            '3.3.3' => ['label' => 'Accruals', 'rico_property' => 'rico:accruals', 'rico_class' => null, 'required' => false],
            '3.3.4' => ['label' => 'System of arrangement', 'rico_property' => 'rico:structure', 'rico_class' => null, 'required' => false],

            // 3.4 Conditions of Access and Use Area
            '3.4.1' => ['label' => 'Conditions governing access', 'rico_property' => 'rico:conditionsOfAccess', 'rico_class' => null, 'required' => false],
            '3.4.2' => ['label' => 'Conditions governing reproduction', 'rico_property' => 'rico:conditionsOfUse', 'rico_class' => null, 'required' => false],
            '3.4.3' => ['label' => 'Language/scripts of material', 'rico_property' => 'rico:hasOrHadLanguage', 'rico_class' => null, 'required' => false],
            '3.4.4' => ['label' => 'Physical characteristics and technical requirements', 'rico_property' => 'rico:physicalCharacteristics', 'rico_class' => null, 'required' => false],
            '3.4.5' => ['label' => 'Finding aids', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],

            // 3.5 Allied Materials Area
            '3.5.1' => ['label' => 'Existence and location of originals', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],
            '3.5.2' => ['label' => 'Existence and location of copies', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],
            '3.5.3' => ['label' => 'Related units of description', 'rico_property' => 'rico:isOrWasRelatedTo', 'rico_class' => null, 'required' => false],
            '3.5.4' => ['label' => 'Publication note', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],

            // 3.6 Notes Area
            '3.6.1' => ['label' => 'Note', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],

            // 3.7 Description Control Area
            '3.7.1' => ['label' => "Archivist's note", 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],
            '3.7.2' => ['label' => 'Rules or conventions', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null, 'required' => false],
            '3.7.3' => ['label' => 'Date(s) of descriptions', 'rico_property' => 'rico:isAssociatedWithDate', 'rico_class' => 'rico:SingleDate', 'required' => false],
        ],

        'isaar_cpf' => [
            // 5.1 Identity Area
            '5.1.1' => ['label' => 'Type of entity', 'rico_property' => 'rdf:type', 'rico_class' => null],
            '5.1.2' => ['label' => 'Authorized form(s) of name', 'rico_property' => 'rico:hasAgentName', 'rico_class' => 'rico:AgentName'],
            '5.1.3' => ['label' => 'Parallel forms of name', 'rico_property' => 'rico:hasAgentName', 'rico_class' => 'rico:AgentName'],
            '5.1.4' => ['label' => 'Standardized forms of name according to other rules', 'rico_property' => 'rico:hasAgentName', 'rico_class' => 'rico:AgentName'],
            '5.1.5' => ['label' => 'Other forms of name', 'rico_property' => 'rico:hasAgentName', 'rico_class' => 'rico:AgentName'],
            '5.1.6' => ['label' => 'Identifiers for corporate bodies', 'rico_property' => 'rico:identifier', 'rico_class' => null],

            // 5.2 Description Area
            '5.2.1' => ['label' => 'Dates of existence', 'rico_property' => 'rico:isAssociatedWithDate', 'rico_class' => 'rico:DateRange'],
            '5.2.2' => ['label' => 'History', 'rico_property' => 'rico:history', 'rico_class' => null],
            '5.2.3' => ['label' => 'Places', 'rico_property' => 'rico:hasOrHadLocation', 'rico_class' => 'rico:Place'],
            '5.2.4' => ['label' => 'Legal status', 'rico_property' => 'rico:hasOrHadLegalStatus', 'rico_class' => null],
            '5.2.5' => ['label' => 'Functions, occupations and activities', 'rico_property' => 'rico:performsOrPerformed', 'rico_class' => 'rico:Activity'],
            '5.2.6' => ['label' => 'Mandates/sources of authority', 'rico_property' => 'rico:isOrWasRegulatedBy', 'rico_class' => 'rico:Mandate'],
            '5.2.7' => ['label' => 'Internal structures/genealogy', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null],
            '5.2.8' => ['label' => 'General context', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null],

            // 5.3 Relationships Area
            '5.3.1' => ['label' => 'Names/identifiers of related corporate bodies, persons or families', 'rico_property' => 'rico:isAgentAssociatedWithAgent', 'rico_class' => 'rico:Agent'],
            '5.3.2' => ['label' => 'Category of relationship', 'rico_property' => 'rico:agentRelationType', 'rico_class' => null],
            '5.3.3' => ['label' => 'Description of relationship', 'rico_property' => 'rico:descriptiveNote', 'rico_class' => null],
            '5.3.4' => ['label' => 'Dates of relationship', 'rico_property' => 'rico:isAssociatedWithDate', 'rico_class' => 'rico:DateRange'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ISAD(G) Level to RiC-O Class Mapping
    |--------------------------------------------------------------------------
    */
    'level_to_rico' => [
        'fonds' => 'rico:RecordSet',
        'subfonds' => 'rico:RecordSet',
        'collection' => 'rico:RecordSet',
        'series' => 'rico:RecordSet',
        'subseries' => 'rico:RecordSet',
        'file' => 'rico:RecordSet',
        'subfile' => 'rico:RecordSet',
        'item' => 'rico:Record',
        'part' => 'rico:RecordPart',
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Type to RiC-O Class Mapping
    |--------------------------------------------------------------------------
    */
    'agent_type_to_rico' => [
        'person' => 'rico:Person',
        'corporate_body' => 'rico:CorporateBody',
        'family' => 'rico:Family',
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    */
    'elasticsearch' => [
        'host' => env('ELASTICSEARCH_HOST', 'localhost'),
        'port' => (int) env('ELASTICSEARCH_PORT', 9200),
        'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
        'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'openric_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Qdrant Configuration
    |--------------------------------------------------------------------------
    */
    'qdrant' => [
        'host' => env('QDRANT_HOST', 'localhost'),
        'port' => (int) env('QDRANT_PORT', 6333),
        'collection' => env('QDRANT_COLLECTION', 'openric_entities'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    */
    'ollama' => [
        'endpoint' => env('OLLAMA_ENDPOINT', 'http://localhost:11434'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    ],

];
