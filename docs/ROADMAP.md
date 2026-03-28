# OpenRiC Roadmap

## Vision

A standalone, RiC-O native archival platform with plugin architecture. Every archival entity is a graph node, every relationship is a triple, and every traditional standard is a rendered lens — not a data model. All Heratio functionality adapted as independent OpenRiC packages.

---

## Phase 1 — Foundation (v0.1) — COMPLETE

### Infrastructure
- [x] PostgreSQL 16 — operational data, auth, sessions, audit
- [x] Apache Jena Fuseki dataset — RiC-O triplestore
- [x] Laravel 12 application skeleton with monorepo package architecture
- [x] Bootstrap 5 theme (WCAG 2.1 Level AA) with view switch
- [x] Authentication + ACL + security clearance (5 levels)
- [x] Audit trail with old/new value tracking
- [x] 10 database migrations, 71 permissions, 4 roles, 5 security classifications
- [x] Elasticsearch connection layer (SearchService 913 lines)
- [x] Qdrant connection layer (SearchService + OllamaEmbeddingService)

### Core Packages (adapted from Heratio)
- [x] `openric-core` — settings service, traits, browse interface
- [x] `openric-triplestore` — TriplestoreService interface + Fuseki implementation
- [x] `openric-theme` — Bootstrap 5 layouts, sidebar nav, view switch toggle
- [x] `openric-auth` — auth, ACL, roles, permissions, security clearance
- [x] `openric-audit` — audit trail, change logging
- [x] `openric-provenance` — RDF-Star provenance, Activity model

### Core RiC-O Entities — Full CRUD
- [x] `rico:Record`
- [x] `rico:RecordSet`
- [x] `rico:RecordPart`
- [x] `rico:Agent` (Person, CorporateBody, Family)
- [x] `rico:Activity`
- [x] `rico:Place`
- [x] `rico:Date`
- [x] `rico:Mandate`
- [x] `rico:Function`
- [x] `rico:Instantiation`

### Relationships — Full CRUD
- [x] `rico:hasOrHadCreator`
- [x] `rico:hasOrHadSubject`
- [x] `rico:hasOrHadInstantiation`
- [x] `rico:describesOrDescribed`
- [x] `rico:isOrWasRelatedTo`
- [x] `rico:hasOrHadPart`
- [x] `rico:isOrWasIncludedIn`
- [x] `rico:hasOrHadHolder`

### Feature Packages (adapted from Heratio)
- [x] `openric-search` — Elasticsearch + Qdrant + SPARQL semantic search
- [x] `openric-ai` — Ollama embeddings, AI-assisted description
- [x] `openric-authority` — Wikidata/VIAF/LCNAF linking
- [x] `openric-condition` — Spectrum condition assessments
- [x] `openric-workflow` — Multi-step approval workflows

### Traditional Lenses — COMPLETE (Phase 2, v0.2.0)
- [x] ISAD(G) view lens — all 26 elements rendered from SPARQL (RecordSet, Record, RecordPart)
- [x] ISAD(G) input form — writes RiC-O triples via StandardsMappingService
- [x] ISAAR-CPF view lens (Person, CorporateBody, Family)
- [x] ISAAR-CPF input form — writes RiC-O triples via StandardsMappingService
- [x] Standards mapping admin UI — /admin/mappings showing all ISAD(G)→RiC-O and ISAAR-CPF→RiC-O tables
- [x] Hierarchical browse — fonds/series/file/item tree with expand/collapse
- [x] Traditional finding aid print view — recursive ISAD(G) rendering
- [x] Relationship CRUD UI — add/remove relationships between entities
- [x] View switch toggle — RiC / Traditional per session with middleware

---

## Phase 3 — Graph View (v0.3) — COMPLETE

- [x] Cytoscape.js relationship visualiser (openric-graph package)
- [x] Entity-centred graph view with force-directed layout, colour-coded by RiC-O type
- [x] Graph overview — all RecordSets and relationships
- [x] Timeline view — date-based traversal via rico:isAssociatedWithDate
- [x] Agent network view — who created what via rico:hasOrHadCreator
- [x] Three-way view toggle: RiC / Traditional / Graph
- [x] JSON API endpoint for graph data (/graph/entity/{iri}/json)
- [x] GraphService with entity graph, agent network, timeline, overview methods

---

## Phase 4 — Provenance + Audit (v0.4) — COMPLETE

- [x] RDF-Star annotation on every triple write (FusekiTriplestoreService, 870 lines)
- [x] Audit trail — full AuditService adapted from Heratio (301 lines), statistics, export, entity history
- [x] Description history view — combined RDF-Star + PostgreSQL audit_log timeline
- [x] Description-as-Record model per RiC-CM Section 6 / EGAD guidance
- [x] PROV-O mapping (prov:wasAttributedTo on description records)
- [x] Certainty/confidence annotations on relationships via RDF-Star (CertaintyService)
- [x] Provenance Activity model — 8 activity types with timeline, custody chain, agent activities (423 lines, adapted from Heratio ric_provenance.py)
- [x] All services rewritten from Heratio source: SecurityClearanceService (407), WorkflowService (898), SearchService (913), AuthorityService (790), ProvenanceService (423), AuditService (301)

---

## Phase 5 — Discovery (v0.5) — COMPLETE

- [x] Full-text search via Elasticsearch (SearchService 913 lines, fuzzy/wildcard/phrase, field boosting)
- [x] SPARQL endpoint (public, read-only, CORS-enabled, query form UI)
- [x] Semantic search via Qdrant (vector similarity in SearchService)
- [x] OAI-PMH 2.0 harvesting endpoint (adapted from Heratio 927-line controller, 6 verbs, Dublin Core from RiC-O)
- [x] Faceted browse (SPARQL-driven, entity type + creator facets, sorting, pagination)

---

## Phase 6 — Export + Interoperability (v0.6) — COMPLETE

- [x] EAD3 export (recursive hierarchy from SPARQL, 1,074-line ExportService)
- [x] EAC-CPF export (agent authority records)
- [x] JSON-LD export (adapted from Heratio, @context/@graph with RiC-O)
- [x] Turtle / RDF/XML export (CONSTRUCT queries serialised)
- [x] Dublin Core export (RiC-O → DC mapping)
- [x] IIIF Presentation API 3.0 manifests + collections (559-line IiifService, adapted from Heratio 647-line)
- [x] Bulk export (multiple entities, any format)
- [x] openric-export package: 2,078 lines, 8 files

---

## Phase 7 — Workflow + Publication (v0.7) — COMPLETE

- [x] Multi-step description approval workflow (WorkflowService 898 lines + WorkflowController 357 lines)
- [x] Dashboard: my tasks, pool tasks, stats, overdue
- [x] Task claim/release/approve/reject with history timeline
- [x] Workflow admin: create/edit/delete workflows + steps
- [x] Publish readiness gate evaluation per entity
- [x] Pool-based task assignment
- [x] 12 files, 2,087 lines, 9 Blade views (adapted from Heratio)

---

## Standards Compliance Targets

| Standard | Phase 1 | Phase 2 | Phase 3+ |
|---|---|---|---|
| RiC-O 1.1 | Core entities | Full property set | Complete |
| RiC-CM 1.0 | Core model | Full model | Complete |
| ISAD(G) | — | All 26 elements | Complete |
| ISAAR-CPF | — | Full | Complete |
| EAD3 | — | — | Export |
| OAI-PMH | — | — | Phase 5 |
| PROV-O | — | — | Phase 4 |