# OpenRiC Roadmap

## Vision

A standalone, RiC-O native archival platform with plugin architecture. Every archival entity is a graph node, every relationship is a triple, and every traditional standard is a rendered lens — not a data model. All Heratio functionality adapted as independent OpenRiC packages.

---

## Phase 1 — Foundation (v0.1) — IN PROGRESS

### Infrastructure
- [x] PostgreSQL 16 — operational data, auth, sessions, audit
- [x] Apache Jena Fuseki dataset — RiC-O triplestore
- [x] Laravel 12 application skeleton with monorepo package architecture
- [x] Bootstrap 5 theme (WCAG 2.1 Level AA) with view switch
- [x] Authentication + ACL + security clearance (5 levels)
- [x] Audit trail with old/new value tracking
- [x] 10 database migrations, 71 permissions, 4 roles, 5 security classifications
- [ ] Elasticsearch connection layer
- [ ] Qdrant connection layer

### Core Packages (adapted from Heratio)
- [x] `openric-core` — settings service, traits, browse interface
- [x] `openric-triplestore` — TriplestoreService interface + Fuseki implementation
- [x] `openric-theme` — Bootstrap 5 layouts, sidebar nav, view switch toggle
- [x] `openric-auth` — auth, ACL, roles, permissions, security clearance
- [x] `openric-audit` — audit trail, change logging
- [ ] `openric-provenance` — RDF-Star provenance, Activity model

### Core RiC-O Entities — Full CRUD
- [ ] `rico:Record`
- [ ] `rico:RecordSet`
- [ ] `rico:RecordPart`
- [ ] `rico:Agent` (Person, CorporateBody, Family)
- [ ] `rico:Activity`
- [ ] `rico:Place`
- [ ] `rico:Date`
- [ ] `rico:Mandate`
- [ ] `rico:Function`
- [ ] `rico:Instantiation`

### Relationships — Full CRUD
- [ ] `rico:hasOrHadCreator`
- [ ] `rico:hasOrHadSubject`
- [ ] `rico:hasOrHadInstantiation`
- [ ] `rico:describesOrDescribed`
- [ ] `rico:isOrWasRelatedTo`
- [ ] `rico:hasOrHadPart`
- [ ] `rico:isOrWasIncludedIn`
- [ ] `rico:hasOrHadHolder`

### Feature Packages (adapted from Heratio)
- [ ] `openric-search` — Elasticsearch + Qdrant + SPARQL semantic search
- [ ] `openric-ai` — Ollama embeddings, AI-assisted description
- [ ] `openric-authority` — Wikidata/VIAF/LCNAF linking
- [ ] `openric-condition` — Spectrum condition assessments
- [ ] `openric-workflow` — Multi-step approval workflows

### Traditional Lenses (included in Phase 1)
- [ ] ISAD(G) view lens — all 26 elements rendered from SPARQL
- [ ] ISAD(G) input form — writes RiC-O triples
- [ ] ISAAR-CPF view lens
- [ ] ISAAR-CPF input form
- [ ] Standards mapping table — ISAD(G) field → RiC-O property
- [ ] Hierarchical browse (fonds/series/file/item) rendered from graph
- [ ] Traditional finding aid print view

---

## Phase 3 — Graph View (v0.3)

- [ ] D3.js / Cytoscape.js relationship visualiser
- [ ] Entity-centred graph view (expand/collapse relationships)
- [ ] Timeline view (date-based traversal)
- [ ] Agent network view (who created what)
- [ ] Toggle between traditional view and graph view per record

---

## Phase 4 — Provenance + Audit (v0.4)

- [ ] RDF-Star annotation on every triple write
- [ ] Audit trail — who changed what triple, when
- [ ] Description history view per record
- [ ] PROV-O mapping for description provenance
- [ ] Certainty/confidence annotation on relationships

---

## Phase 5 — Discovery (v0.5)

- [ ] Full-text search via OpenSearch
- [ ] SPARQL endpoint (public, read-only)
- [ ] Semantic search via Qdrant
- [ ] OAI-PMH harvesting endpoint
- [ ] Faceted browse

---

## Phase 6 — Export + Interoperability (v0.6)

- [ ] EAD3 export (generated from SPARQL)
- [ ] EAC-CPF export
- [ ] JSON-LD export
- [ ] Turtle / RDF/XML export
- [ ] Dublin Core export
- [ ] IIIF manifest generation per Instantiation
- [ ] Bulk export

---

## Phase 7 — Workflow + Publication (v0.7)

- [ ] Multi-step description approval workflow
- [ ] Draft / review / published status per record
- [ ] Access restriction management
- [ ] Public portal view (read-only)

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