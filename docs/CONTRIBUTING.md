# Contributing to OpenRiC

Thank you for your interest in OpenRiC. Contributions from archivists, developers, standards experts, and institutions are welcome.

---

## Ways to Contribute

- **Code** — Laravel backend, frontend, SPARQL queries, export formats
- **Standards expertise** — RiC-O modelling, ISAD(G)/ISAAR-CPF mapping review
- **Testing** — real-world archival data, edge cases, accessibility
- **Documentation** — user guides, API docs, modelling examples
- **Translation** — interface strings in any language
- **Issues** — bug reports, feature requests, standards questions

---

## Development Setup

Requirements:
- PHP 8.3+
- Laravel 12
- PostgreSQL 15+
- Apache Jena Fuseki 4.x
- OpenSearch 2.x
- Qdrant
- Node.js (frontend assets)

Setup instructions: see [docs/setup.md](docs/setup.md) (coming Phase 1)

---

## Standards

All RiC-O modelling must align with:
- [RiC-O 1.1](https://www.ica.org/standards/RiC/RiC-O_1-1.html)
- [RiC-CM 1.0](https://github.com/ICA-EGAD/RiC-CM)
- [RiC Application Guidelines](https://ica-egad.github.io/RiC-AG/)

When in doubt, the [Records in Contexts users Google Group](https://groups.google.com/g/Records_in_Contexts_users) is the authoritative community resource.

---

## Code Standards

- PHP CS Fixer compliant
- Laravel conventions throughout
- No raw SQL — Eloquent or Query Builder only
- Every SPARQL write must include RDF-Star provenance annotation
- WCAG 2.1 Level AA for all UI changes

---

## Pull Request Process

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit with clear messages referencing any related issue
4. Open a pull request with a description of changes and standards alignment
5. At least one review required before merge

---

## Code of Conduct

OpenRiC follows the [Contributor Covenant](https://www.contributor-covenant.org/) Code of Conduct. Be respectful, constructive, and collaborative.

---

## Questions

Open an issue or post to the [Records in Contexts users Google Group](https://groups.google.com/g/Records_in_Contexts_users).