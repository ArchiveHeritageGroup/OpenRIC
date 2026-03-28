# OpenRiC — Vision, Architecture & Design Decisions

## Why OpenRiC Exists

### The Problem

Every major archival platform in existence today — AtoM, ArchivesSpace, Atom4, Archivematica, Preservica — stores archival descriptions in relational databases designed around hierarchical standards like ISAD(G). RiC-O is treated as an export format, a bolt-on, or a future aspiration. No platform has been built from the ground up with RiC-O as its native storage and operational layer.

This matters because RiC-O is not just another export format. It is a fundamentally different way of thinking about archival description — from hierarchy to graph, from fixed fields to relationships, from isolated finding aids to a global web of connected archival knowledge.

The archival community has invested years in developing RiC-CM and RiC-O. The tooling has not kept pace.

### The Gap OpenRiC Fills

| What exists | What was missing |
|---|---|
| Platforms that export to RiC-O | A platform that stores natively in RiC-O |
| Static RiC-O example files from EGAD | A live, queryable, editable RiC-O platform |
| RiC-O ontology documentation | Full CRUD on RiC-O entities and relationships |
| Triplestores that hold archival data | A purpose-built archival interface on top of a triplestore |
| Traditional standards (ISAD(G), ISAAR-CPF) | Those standards as rendered lenses on a RiC-O graph |

OpenRiC is the first open-source platform to implement RiC-O as a native storage and CRUD layer, with traditional archival standards rendered as views from the same graph.

---

## What OpenRiC Is

OpenRiC is a standalone, open-source archival platform where:

- **RiC-O 1.1 is the canonical data layer** — all archival entities and relationships are stored as RiC-O triples
- **Traditional standards are lenses** — ISAD(G), ISAAR-CPF, Dublin Core are rendered from SPARQL queries, not stored as the underlying model
- **Full CRUD on the graph** — archivists can create, read, update, and delete RiC-O entities directly
- **RDF-Star provenance** — every triple change carries who made it, when, and on what basis
- **Multi-standard export** — EAD3, EAC-CPF, JSON-LD, Turtle, RDF/XML generated on demand
- **Multi-standard input** — ISAD(G) and ISAAR-CPF forms write RiC-O triples transparently

OpenRiC is not a module, plugin, or extension of any existing platform. It is a greenfield build on modern infrastructure, informed by years of production archival systems development.

---

## What OpenRiC Is Not

- Not a replacement for AtoM — AtoM is a mature, proven platform. OpenRiC is complementary, targeting institutions that want native RiC-O
- Not a records management system — OpenRiC focuses on archival description and linked data delivery
- Not cloud-dependent — designed for self-hosted deployment at any scale
- Not AHG-proprietary — released under AGLP 3.0 licence for the international archival community

---

## Design Principles

**1. RiC-O first, always**
Every design decision is evaluated against RiC-O 1.1 and RiC-CM 1.0. The ICA EGAD community and the Records in Contexts users Google Group are the authoritative reference points.

**2. Archivists should never see a triple**
The interface presents familiar archival concepts — descriptions, authority records, relationships, hierarchies. The RiC-O graph is the engine, not the dashboard.

**3. Traditional standards are not deprecated**
ISAD(G) and ISAAR-CPF represent decades of archival practice. OpenRiC respects them as input and output formats. The standards mapping layer is explicit, documented, and community-maintained.

**4. Provenance on everything**
RDF-Star annotations on every triple change. Description history is a first-class feature, not an afterthought. This reflects RiC-CM's treatment of description as a Record in its own right.

**5. Abstracted infrastructure**
No hard dependency on any single triplestore, search engine, or storage backend. OpenRiC ships with sensible defaults but institutions can swap components without touching application code.

**6. Open standards, open source, open community**
[AGPL v3 License](LICENSE). Public roadmap. Community contributions welcome. The Records in Contexts users Google Group is the primary community channel.

---

## The RiC-O Data Model in OpenRiC

### Core Entities (full CRUD)

| RiC-O Class | Archival concept |
|---|---|
| `rico:Record` | Individual archival record |
| `rico:RecordSet` | Fonds, series, file |
| `rico:RecordPart` | Item, fragment |
| `rico:Agent` | Person, corporate body, family |
| `rico:Activity` | Event, transaction, process |
| `rico:Place` | Geographic entity |
| `rico:Date` | Date, date range, period |
| `rico:Mandate` | Law, regulation, authority |
| `rico:Function` | Functional classification |
| `rico:Instantiation` | Physical or digital carrier |

### Key Relationships (full CRUD)

- `rico:hasOrHadCreator` — record to creating agent
- `rico:hasOrHadInstantiation` — record to physical/digital carrier
- `rico:describesOrDescribed` — description record to described entity
- `rico:isOrWasRelatedTo` — general relationship
- `rico:hasOrHadPart` / `rico:isOrWasIncludedIn` — hierarchy
- `rico:hasOrHadHolder` — custodial history
- `rico:isAssociatedWithDate` — temporal context
- `rico:isAssociatedWithPlace` — spatial context

### Description Provenance (RDF-Star)

Every triple write is annotated:

```turtle
<< :record001 rico:hasCreationDate "1942-03-15" >>
    rico:wasAttributedTo :archivist-jane ;
    dcterms:created "2026-01-15"^^xsd:date ;
    rdfs:comment "Inferred from postmark" .
```

This reflects the RiC-CM principle that descriptions are themselves Records, and aligns with the ICA EGAD guidance that RiC-O natively handles description provenance via `rico:describesOrDescribed`.

---

## Traditional Standards as Lenses

OpenRiC maps each traditional standard to RiC-O and renders views dynamically from SPARQL queries.

### ISAD(G) → RiC-O Mapping (selected)

| ISAD(G) Element | RiC-O Equivalent |
|---|---|
| Reference code | `rico:hasOrHadIdentifier` |
| Title | `rico:title` |
| Date(s) | `rico:isAssociatedWithDate` → `rico:Date` |
| Level of description | `rico:RecordResource` subclass |
| Extent | `rico:hasExtent` |
| Creator | `rico:hasOrHadCreator` → `rico:Agent` |
| Scope and content | `rico:scopeAndContent` |
| Arrangement | `rico:hasOrHadStructure` |
| Access conditions | `rico:hasOrHadAccessRight` |
| Language | `rico:hasOrHadLanguage` |

Full mapping table: see `docs/mappings/isadg-rico.md`

### ISAAR-CPF → RiC-O Mapping

ISAAR-CPF authority records map to `rico:Agent` and its subclasses (`rico:Person`, `rico:CorporateBody`, `rico:Family`) with associated relationships and dates.

Full mapping table: see `docs/mappings/isaarcpf-rico.md`

---

## Technical Architecture

### Stack

```
┌─────────────────────────────────────────────────────────┐
│  Laravel 12 (PHP 8.3) — Application Layer               │
│  Bootstrap 5 — WCAG 2.1 Level AA                        │
│  D3.js / Cytoscape.js — Graph visualisation             │
├──────────────┬──────────────┬────────────┬──────────────┤
│  PostgreSQL  │  Triplestore │ OpenSearch │  Qdrant      │
│  15+         │  Service     │ 2.x        │              │
│              │  (abstracted)│            │              │
│  Auth        │  Default:    │ Full-text  │ Semantic /   │
│  Sessions    │  Fuseki 4.x  │ search     │ vector       │
│  Jobs        │  17.9M+      │            │ search       │
│  Operational │  triples     │            │              │
└──────────────┴──────────────┴────────────┴──────────────┘
```

### Why Each Component

**Laravel 12**
Mature PHP framework. Proven in production archival systems. Strong community. Eloquent ORM for operational data. Queue system for async jobs. Package architecture supports modular extraction and reuse.

**PostgreSQL 15+**
Operational data only — authentication, sessions, job queues, audit log. Not used for archival description content. Superior to MySQL for this role: native JSONB, better full-text, array types, more robust at scale.

**TriplestoreService (abstracted)**
OpenRiC does not hard-code a triplestore. A Laravel service interface sits between the application and the triplestore. Default implementation: Apache Jena Fuseki 4.x. Institutions can substitute GraphDB, Oxigraph, or Virtuoso without modifying application code.

**Apache Jena Fuseki 4.x (default)**
Most widely known open-source triplestore in the archival/linked data community. Full SPARQL 1.1. RDF-Star from v4.x. Zero barrier to entry for institutions already familiar with it. Deployed bare metal (not Docker) for performance.

**OpenSearch 2.x**
Full-text search across archival descriptions. Faster and more scalable than SPARQL full-text for researcher-facing search. Fed by an indexing agent that listens to triple writes.

**Qdrant**
Vector/semantic search. Enables natural language discovery — "find records similar to this one" — beyond keyword matching. Aligns with the vision of AI-assisted archival discovery anchored in provenance.

**Bootstrap 5 / WCAG 2.1 AA**
Accessibility is non-negotiable for public archival platforms. Bootstrap 5 provides a consistent, accessible foundation. All UI components meet WCAG 2.1 Level AA.

**D3.js / Cytoscape.js**
Graph visualisation of RiC-O relationships. The visual interface that makes the graph meaningful to archivists and researchers who have never seen a triple.

---

## Views

Every record in OpenRiC can be viewed through multiple lenses:

**Traditional view**
ISAD(G) or ISAAR-CPF layout. Familiar to any trained archivist. Generated from SPARQL, rendered as a standard finding aid. Printable.

**Graph view**
D3.js force-directed or Cytoscape.js network. Shows the entity at the centre with all its RiC-O relationships radiating outward. Expandable, navigable, explorable.

**Linked data view**
Raw RiC-O in Turtle, JSON-LD, or RDF/XML. For developers, systems integrators, and linked data practitioners.

**Timeline view**
Date-based traversal of records and agents. Useful for research and exhibition contexts.

---

## Relationship to Heratio

OpenRiC shares architectural DNA with [Heratio](https://theahg.co.za), The Archive and Heritage Group's full GLAM + Records Management platform. Several Laravel packages are extracted and adapted from Heratio's production codebase:

- Workflow engine
- Audit trail
- ACL / security layer
- Fuseki integration
- Search abstraction
- Bootstrap 5 theme

OpenRiC has no runtime dependency on Heratio and does not require AtoM. It is a fully standalone platform.

---

## Deployment

OpenRiC is designed for self-hosted deployment. Target environments:

- National archives
- University archives and libraries
- Municipal and regional archives
- GLAM institutions of any size

Minimum recommended server: 16GB RAM, 4 cores, SSD storage.
Large collections (1M+ records): 64GB RAM, dedicated Fuseki instance, NFS storage.

Docker support: planned for development environments. Production deployment: bare metal recommended for Fuseki performance.

---

## Community & Governance

OpenRiC is developed by The Archive and Heritage Group and released to the international archival community under the AGPL 3.0 licence.

Community channels:
- GitHub: `github.com/ArchiveHeritageGroup/OpenRiC`
- ICA Records in Contexts users Google Group: `Records_in_Contexts_users@googlegroups.com`

Standards reference:
- [RiC-O 1.1](https://www.ica.org/standards/RiC/RiC-O_1-1.html)
- [RiC-CM 1.0](https://github.com/ICA-EGAD/RiC-CM)
- [RiC Application Guidelines](https://ica-egad.github.io/RiC-AG/)

---

## Academic Context

OpenRiC emerges from active PhD research in AI applied to records management and archival description. It is intended as both a practical platform and a contribution to the academic literature on RiC-O implementation.

Relevant publications and presentations:
- SAMAB 2026 (deadline 30 May 2026)
- ESARBICA
- SAJIM

---

*OpenRiC — open-source, RiC-O native, built for the international archival community.*  
*Developed by The Archive and Heritage Group — theahg.co.za*