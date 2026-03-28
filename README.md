
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

- **Native RiC-O CRUD** — create and edit RiC-O entities (RecordResource, Agent, Activity, Place, Date, Mandate, Function) directly
- **Traditional view** — ISAD(G) and ISAAR-CPF rendered from SPARQL — familiar to any archivist
- **Graph view** — D3.js force-directed visualisation of RiC-O relationships
- **RDF-Star audit trail** — provenance annotations on every triple
- **Multi-standard export** — EAD3, EAC-CPF, JSON-LD, Turtle, RDF/XML
- **Semantic search** — Qdrant vector search across descriptions
- **Full-text search** — OpenSearch
- **SPARQL endpoint** — queryable by external systems
- **OAI-PMH** — harvestable by aggregators
- **WCAG 2.1 Level AA** — accessible by design
- **Multi-language** — internationalised interface
- **Workflow** — multi-step approval for description publication
- **Audit trail** — every change logged with RDF-Star + relational audit

---

## Relationship to Heratio

OpenRiC shares architectural components with [Heratio](https://theahg.co.za), the full GLAM + Records Management platform by The Archive and Heritage Group. OpenRiC is a **standalone platform** — it does not require Heratio and has no dependency on AtoM.

Shared components: Laravel package architecture, Bootstrap 5 theme, ACL layer, workflow engine, audit trail, Fuseki integration, Qdrant semantic search.

---

## Status

🚧 **Active development — pre-release**

OpenRiC is under active development. The first public release will target:

- Full CRUD for core RiC-O entities
- ISAD(G) and ISAAR-CPF traditional view lenses
- Graph visualisation
- EAD3 / JSON-LD export
- SPARQL endpoint
- OAI-PMH

---

## Roadmap

See [ROADMAP.md](ROADMAP.md) for the full build plan.

---

## Contributing

OpenRiC welcomes contributions from the international archival community — archivists, developers, standards experts, and institutions.

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## Community

Discussions, questions, and implementation experience welcome on the [ICA Records in Contexts users Google Group](https://groups.google.com/g/Records_in_Contexts_users).

---

## License

[MIT License](LICENSE) — free to use, modify, and distribute.

---

## Developed By

[The Archive and Heritage Group](https://theahg.co.za)  
Johan Pieterse  
[theahg.co.za](https://theahg.co.za)

---

*OpenRiC is the first open-source platform to implement RiC-O as a native storage and CRUD layer with multi-standard lens support. No other open-source archival platform covers RiC-O, ISAD(G), ISAAR-CPF, and linked data delivery in a single stack.*
