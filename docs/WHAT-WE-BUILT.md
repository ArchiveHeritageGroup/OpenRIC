# What OpenRiC Is — A Plain English Guide

## The Short Version

OpenRiC is a complete GLAM (Galleries, Libraries, Archives, Museums) and DAM (Digital Asset Management) platform that stores every piece of description as linked data using the international RiC-O standard. Archivists, librarians, and curators work with familiar forms (ISAD(G), ISAAR-CPF, Spectrum). Behind the scenes, everything is stored as a knowledge graph — connected, queryable, and exportable in any format.

OpenRiC covers the full lifecycle: description, access control, workflow approval, condition assessment, digital asset management, provenance tracking, discovery, export, and publication.

---

## What Problem Does It Solve?

Traditional archival systems store descriptions in rigid database tables designed around hierarchical standards from the 1990s. When the international archival community developed Records in Contexts (RiC) — a modern, graph-based standard — existing systems treated it as an export format, not a storage layer.

OpenRiC is the first platform built from the ground up with RiC as its native data model. This means:

- **Relationships are first-class citizens.** A record can be linked to its creator, its custodians, its subjects, its places, its dates — all as explicit, navigable connections. Not just foreign keys in a table.
- **Traditional standards still work.** Archivists who know ISAD(G) can fill in the 26 familiar fields. The system maps those fields to RiC triples automatically. Nothing changes for the archivist. Everything changes under the hood.
- **Every change is traceable.** Every edit carries who made it, when, and why. This isn't just an audit log — it's built into the data itself using RDF-Star provenance annotations.

---

## What Can You Do With It?

### Describe Archival Material

Create and manage descriptions for 10 types of archival entities:

| Entity | What It Represents |
|---|---|
| Record Set | A fonds, series, sub-series, or file |
| Record | An individual item |
| Record Part | A fragment or component of an item |
| Person | An individual (creator, subject, custodian) |
| Corporate Body | An organisation |
| Family | A family unit |
| Activity | An event, process, or transaction |
| Place | A geographic location |
| Mandate | A law, regulation, or source of authority |
| Function | A functional classification |
| Instantiation | A physical or digital carrier (the actual document, photo, file) |

Each entity supports full create, read, update, and delete operations. Entities are connected to each other through named relationships — "this record was created by this person," "this series is held by this repository," "this item is part of this file."

### Switch Between Views

Every record can be viewed three ways:

- **RiC View** — shows the native graph properties and relationships
- **Traditional View** — renders the same data as an ISAD(G) description (for records) or ISAAR-CPF authority record (for agents)
- **Graph View** — shows the entity at the centre of an interactive network visualisation, with all its connections radiating outward

Toggle between them with a single click. The data is the same — only the presentation changes.

### Browse and Search

- **Hierarchical Browse** — navigate the archival hierarchy from fonds down to item level, expanding and collapsing levels
- **Faceted Browse** — filter by entity type, creator, date range, with live facet counts
- **Full-Text Search** — powered by Elasticsearch with fuzzy matching, phrase search, and field boosting (titles weighted higher than content)
- **Semantic Search** — find records similar to a given record using AI vector embeddings, not just keyword matching
- **SPARQL Query** — for advanced users, a public SPARQL endpoint with a built-in query editor

### Export in Any Format

Export individual records or entire hierarchies in:

- **EAD3** — the standard XML format for finding aids, generated recursively from the graph
- **EAC-CPF** — authority record XML for agents
- **JSON-LD** — linked data JSON with a RiC-O context
- **Turtle** — compact RDF serialisation
- **RDF/XML** — traditional RDF format
- **Dublin Core** — simple metadata for harvesting
- **IIIF Manifests** — for digital objects, compatible with IIIF viewers

Bulk export is supported — select multiple entities and export them all at once.

### Harvest via OAI-PMH

The built-in OAI-PMH 2.0 endpoint allows aggregators and union catalogues to harvest your descriptions automatically. Records are served as Dublin Core, mapped from the underlying RiC-O triples.

### Track Provenance

Every change to every piece of data is tracked at two levels:

1. **Triple-level** — RDF-Star annotations record who changed each specific property, when, and why
2. **Description-level** — following the RiC-CM standard, descriptions are themselves modelled as Records that describe other Records, with their own creation dates and creators

You can view the full history of any entity — a combined timeline showing both the RDF-Star annotations and the PostgreSQL audit log.

Activities (creation, accumulation, transfer, management, digitisation, preservation) are modelled as first-class entities linked to the records they affected, the agents who participated, and the dates they occurred.

### Manage Security

Multi-level security classification from Unclassified to Top Secret:

- Assign classification levels to individual records
- Grant clearance levels to users
- Compartmentalised access — some records require specific compartment access beyond clearance level
- Access request workflow — users can request access to restricted material with justification
- Full audit trail of all security decisions

### Run Approval Workflows

Multi-step workflows for description approval:

- Define workflows with ordered steps (review, approve, publish)
- Assign tasks to specific users or make them available in a pool
- Claim, release, approve, or reject tasks with comments
- Track task history with a visual timeline
- Publish readiness checks — verify an entity meets all gate rules before publication
- Overdue task monitoring

### Link to External Authorities

Connect your agents to international authority files:

- **Wikidata** — search and link via the Wikidata API
- **VIAF** — the Virtual International Authority File
- **LCNAF** — Library of Congress Name Authority File

Links are stored as `owl:sameAs` triples in the graph, making your data part of the global linked data web.

### Assess Condition

Track the physical condition of archival material following the Spectrum 5.0 standard:

- Condition codes (excellent to critical)
- Conservation priority scoring
- Completeness percentage
- Hazard recording
- Storage requirements
- Recommendations
- Scheduled reassessment dates

### AI-Assisted Description

Using local AI models (Ollama), OpenRiC can:

- Generate vector embeddings for semantic similarity search
- Suggest scope and content descriptions based on titles and context
- Find records similar to a given record across the entire collection

---

## How Is It Built?

OpenRiC is a Laravel 12 application (PHP 8.3) with a plugin architecture. The core system works standalone. Every feature is an independent package that can be enabled or disabled.

**18 packages:**

| Category | Packages |
|---|---|
| Core | core, triplestore, theme, auth, audit |
| Entities | record-manage, agent-manage, place-manage, activity-manage, instantiation-manage |
| Features | search, ai, authority, condition, workflow, provenance, export, graph |

**Infrastructure:**

- **PostgreSQL 16** — operational data (users, roles, sessions, audit log, workflows)
- **Apache Jena Fuseki** — RiC-O triplestore (all archival descriptions as linked data triples)
- **Elasticsearch** — full-text search index
- **Qdrant** — vector database for semantic similarity
- **Ollama** — local AI model server for embeddings
- **Bootstrap 5** — accessible UI (WCAG 2.1 Level AA)

The triplestore is abstracted behind an interface — institutions can substitute GraphDB, Oxigraph, or Virtuoso without changing application code.

---

## GLAM and DAM Capabilities

OpenRiC is not just an archival description tool — it is a full GLAM platform with digital asset management:

| GLAM Function | How OpenRiC Delivers It |
|---|---|
| **Archival Description** | 10 RiC-O entity types with ISAD(G) and ISAAR-CPF forms |
| **Authority Control** | Agent management (Person, CorporateBody, Family) with Wikidata/VIAF/LCNAF linking |
| **Digital Asset Management** | Instantiation entity for physical and digital carriers, IIIF manifests, condition tracking |
| **Access Control** | 5-level security classification, compartments, access requests, clearance management |
| **Workflow** | Multi-step approval workflows, pool-based assignment, publish readiness gates |
| **Condition Management** | Spectrum 5.0 condition assessments with conservation priority and scheduled reassessment |
| **Discovery** | Full-text search (Elasticsearch), semantic search (Qdrant/AI), faceted browse, SPARQL |
| **Interoperability** | EAD3, EAC-CPF, JSON-LD, Turtle, RDF/XML, Dublin Core, OAI-PMH, IIIF |
| **Provenance** | RDF-Star triple-level annotations, Activity model, custody chain, description history |
| **Audit** | Complete change tracking with old/new values, statistics, user activity, export |
| **AI** | Ollama embeddings, semantic similarity, AI-assisted description suggestions |

---

## Who Is It For?

- National archives wanting native RiC-O
- University archives and special collections
- Municipal and regional archives
- Museums managing object and collection descriptions
- Libraries with special collections and archives
- GLAM institutions needing integrated description, DAM, and access control
- Researchers who want to query archival data as linked data
- Digital humanities projects needing SPARQL access to archival metadata

---

## Standards Compliance

| Standard | How OpenRiC Uses It |
|---|---|
| RiC-O 1.1 | Native storage layer — all data stored as RiC-O triples |
| RiC-CM 1.0 | Conceptual model — entities and relationships follow RiC-CM |
| ISAD(G) | Input form and view lens — all 26 elements mapped to RiC-O |
| ISAAR-CPF | Input form and view lens for authority records |
| EAD3 | Export format — generated from SPARQL |
| EAC-CPF | Export format for authority records |
| OAI-PMH 2.0 | Harvesting endpoint — Dublin Core from RiC-O |
| Dublin Core | Metadata export and OAI-PMH output |
| IIIF 3.0 | Presentation API manifests for digital objects |
| RDF-Star | Provenance annotations on every triple |
| PROV-O | Description provenance mapping |
| Spectrum 5.0 | Condition assessment framework |
| WCAG 2.1 AA | Accessibility compliance on all UI |

---

## Numbers

| Metric | Count |
|---|---|
| Packages | 18 |
| Source files | 264 |
| Service code | 9,297 lines |
| Routes | 143 |
| Database tables | 24 |
| Permissions | 71 |
| Entity types | 10 + relationships |
| Export formats | 7 |
| Search backends | 3 (Elasticsearch, Qdrant, SPARQL) |

---

## Roadmap Mapping — What Was Built in Each Phase

| Phase | Version | What Was Delivered |
|---|---|---|
| **Phase 1 — Foundation** | v0.1.0 | Laravel 12 skeleton, PostgreSQL, Fuseki triplestore, 18-package plugin architecture, Bootstrap 5 theme (WCAG AA), authentication + ACL + security clearance, audit trail, 10 RiC-O entity types with full CRUD (91 routes), view switch (RiC/Traditional) |
| **Phase 2 — Traditional Lenses** | v0.2.0 | ISAD(G) views for all record entities (26 elements), ISAAR-CPF views for all agent entities, Standards mapping service + admin UI, hierarchical browse (fonds→item tree), traditional finding aid print view, relationship CRUD UI |
| **Phase 3 — Graph View** | v0.3.0 | Cytoscape.js force-directed graph visualisation, entity-centred graph with colour-coded types, agent network view (who created what), timeline view (date-based traversal), three-way toggle (RiC/Traditional/Graph), JSON API for graph data |
| **Phase 4 — Provenance + Audit** | v0.4.0 | All 6 core services rewritten from production codebase: SecurityClearanceService (407 lines), WorkflowService (898), SearchService (913), AuthorityService (790), ProvenanceService (423), AuditService (301). RDF-Star provenance, description-as-Record per RiC-CM Section 6, certainty annotations, PROV-O mapping, description history view |
| **Phase 5 — Discovery** | v0.5.0 | Public SPARQL endpoint with query UI, OAI-PMH 2.0 harvesting (all 6 verbs, Dublin Core from RiC-O), faceted browse with entity type + creator facets, full-text search (Elasticsearch), semantic search (Qdrant) |
| **Phase 6 — Export** | v0.6.0 | openric-export package (2,078 lines): EAD3 (recursive hierarchy), EAC-CPF, JSON-LD, Turtle, RDF/XML, Dublin Core, IIIF Presentation API 3.0 manifests + collections, bulk export |
| **Phase 7 — Workflow + Publication** | v0.7.0 | Workflow UI (2,087 lines, 9 views): dashboard, task management (claim/release/approve/reject), workflow admin (create/edit/delete + steps), publish readiness gate evaluation, overdue monitoring, pool-based assignment |

---

*OpenRiC — open source, RiC-O native, GLAM/DAM platform for the international archival community.*
*AGPL-3.0 | Developed by The Archive and Heritage Group — theahg.co.za*
