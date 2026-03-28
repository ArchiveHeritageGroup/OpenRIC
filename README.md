# OpenRiC

**The world's first open-source, RiC-O native archival platform with full CRUD.**

OpenRiC is a standalone platform for creating, managing, and delivering archival descriptions natively in [Records in Contexts (RiC)](https://www.ica.org/standards/RiC/), the next-generation ICA standard for archival description. It supports traditional archival standards (ISAD(G), ISAAR-CPF, EAD3, EAC-CPF) as **lenses** on a canonical RiC-O graph — not as the underlying data model.

Built by [The Archive and Heritage Group](https://theahg.co.za) and released to the international archival community as free, open-source software.

---

## What OpenRiC Is

Most archival systems store data in hierarchical relational schemas and optionally export to RiC-O. OpenRiC inverts this:

- **RiC-O is the canonical layer** — all archival data is stored as RiC-O triples in Apache Jena Fuseki
- **Traditional standards are lenses** — ISAD(G), ISAAR-CPF, Dublin Core views are rendered from SPARQL queries
- **Full CRUD on the graph** — create, read, update, and delete RiC-O entities and relationships directly
- **RDF-Star provenance** — every triple change is annotated with who changed it, when, and why
- **Multi-standard export** — generate EAD3, EAC-CPF, JSON-LD, Turtle, RDF/XML on demand

---

## Standards Support

| Standard | Role in OpenRiC |
|---|---|
| RiC-O 1.1 | Canonical storage layer |
| RiC-CM 1.0 | Conceptual model |
| ISAD(G) | Input form + traditional view lens |
| ISAAR-CPF | Authority record form + view lens |
| EAD3 | Export format |
| EAC-CPF | Export format |
| Dublin Core | OAI-PMH harvesting |
| RDF-Star | Provenance annotation on triples |
| PROV-O | Description provenance (mapped) |

---

## Architecture
```
┌─────────────────────────────────────────────────────┐
│  Laravel 12 (PHP 8.3) — Application Layer           │
├──────────────┬──────────────┬────────────┬──────────┤
│  PostgreSQL  │  Fuseki      │ OpenSearch │  Qdrant  │
│  Operational │  RiC-O graph │ Full-text  │ Semantic │
│  data + auth │  17.9M+      │ search     │ search   │
│              │  triples     │            │          │
├──────────────┴──────────────┴────────────┴──────────┤
│  Bootstrap 5 — WCAG 2.1 Level AA                    │
│  D3.js / Cytoscape.js — Graph visualisation         │
└─────────────────────────────────────────────────────┘
```

---

## Key Features

- **Native RiC-O CRUD** — create and edit RiC-O entities directly (10 entity types, 8 relationship types)
- **View switch** — toggle between RiC-native view and traditional ISAD(G)/ISAAR-CPF lenses per record
- **Traditional input** — ISAD(G) and ISAAR-CPF forms that write RiC-O triples transparently
- **RDF-Star provenance** — every triple change is annotated with who, when, and why
- **Security clearance** — multi-level classification (Unclassified to Top Secret) with compartments
- **AI-assisted description** — Ollama embeddings, semantic similarity, AI suggestions
- **Condition assessment** — Spectrum 5.0 condition tracking linked to instantiations
- **Authority linking** — Wikidata, VIAF, LCNAF integration via owl:sameAs
- **Semantic search** — Qdrant vector search + Elasticsearch full-text
- **Workflow engine** — multi-step approval workflows with pool-based assignment
- **Audit trail** — comprehensive change logging with old/new value tracking
- **Multi-standard export** — EAD3, EAC-CPF, JSON-LD, Turtle, RDF/XML
- **SPARQL endpoint** — queryable by external systems
- **OAI-PMH** — harvestable by aggregators
- **WCAG 2.1 Level AA** — accessible by design
- **Plugin architecture** — standalone core with optional plugin packages

---

## Relationship to Heratio

OpenRiC is a **standalone platform** that shares architectural DNA with [Heratio](https://theahg.co.za), The Archive and Heritage Group's full GLAM + Records Management platform. All Heratio functionality (audit, security, workflow, search, AI, condition assessment) has been adapted into OpenRiC as independent plugin packages under the `OpenRiC\` namespace.

OpenRiC does **not** require Heratio and has no dependency on AtoM or MySQL. With all plugins disabled, OpenRiC still functions as a complete RiC-O archival platform.

---

## Package Architecture

| Package | Purpose |
|---|---|
| `openric-core` | Settings, shared traits, base services |
| `openric-triplestore` | TriplestoreService interface + Fuseki implementation |
| `openric-theme` | Bootstrap 5 layouts, WCAG AA, view switch |
| `openric-auth` | Authentication, ACL, roles, security clearance |
| `openric-audit` | Audit trail and change logging |
| `openric-provenance` | RDF-Star provenance, Activity model |
| `openric-search` | Elasticsearch + Qdrant + SPARQL search |
| `openric-ai` | Ollama embeddings, AI-assisted description |
| `openric-authority` | Wikidata/VIAF/LCNAF linking |
| `openric-condition` | Spectrum condition assessments |
| `openric-workflow` | Multi-step approval workflows |
| `openric-record-manage` | Record, RecordSet, RecordPart CRUD |
| `openric-agent-manage` | Person, CorporateBody, Family CRUD |
| `openric-place-manage` | Place CRUD |
| `openric-activity-manage` | Activity, Date, Mandate, Function CRUD |
| `openric-instantiation-manage` | Instantiation CRUD |

---

## Status

Active development — Phase 1 (Foundation) in progress

---

## Roadmap

See [ROADMAP.md](ROADMAP.md) for the full build plan.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## Community

Discussions welcome on the [ICA Records in Contexts users Google Group](https://groups.google.com/g/Records_in_Contexts_users).

---

## License

[AGPL-3.0](LICENSE)

---

## Developed By

[The Archive and Heritage Group](https://theahg.co.za)  
Johan Pieterse — [theahg.co.za](https://theahg.co.za)

---

*OpenRiC is the first open-source platform to implement RiC-O as a native storage and CRUD layer with multi-standard lens support.*
