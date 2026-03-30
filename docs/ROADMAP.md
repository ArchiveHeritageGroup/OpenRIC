# OpenRiC Roadmap

**Version:** 1.3.0  
**Date:** 2026-03-29  
**Status:** Active Development

---

## Overview

OpenRiC is an open-source archival platform built natively on **RiC-O 1.1** as the canonical data layer, with traditional archival standards (ISAD(G), ISAAR-CPF, EAD3) rendered as views. All operational data (auth, sessions, jobs, settings) is stored in **PostgreSQL 15+**.

---

## Architecture Summary

```
┌─────────────────────────────────────────────────────────┐
│  Laravel 12 (PHP 8.3) — Application Layer               │
│  Bootstrap 5 — WCAG 2.1 Level AA                        │
│  D3.js / Cytoscape.js — Graph visualisation            │
├──────────────┬──────────────┬────────────┬──────────────┤
│  PostgreSQL  │  Triplestore │ OpenSearch │  Qdrant      │
│  15+         │  Service     │ 2.x        │              │
│              │  (abstracted) │            │              │
│  Auth        │  Default:    │ Full-text  │ Semantic /   │
│  Sessions    │  Fuseki 4.x  │ search     │ vector       │
│  Jobs        │  17.9M+      │            │ search       │
│  Operational │  triples     │            │              │
└──────────────┴──────────────┴────────────┴──────────────┘
```

---

## Current State (v1.3.0)

### ✅ Completed

| Component | Status | Details |
|-----------|--------|---------|
| **Core Framework** | ✅ Complete | Laravel 12, Bootstrap 5, 234 core files |
| **RiC-O Triplestore** | ✅ Complete | Fuseki 4.x integration, SPARQL queries |
| **Package Architecture** | ✅ Complete | 101 packages, 2,449 PHP files |
| **AI Governance** | ✅ Complete | 8 tables, 48 files |
| **IIIF Collection** | ✅ Complete | 29 files |
| **Digital Asset Management** | ✅ Complete | 23 files |
| **Preservation Planning** | ✅ Complete | 28 files |
| **Registry Integration** | ✅ Complete | 172 files |
| **Museum Integration** | ✅ Complete | 44 files |
| **Privacy/GDPR** | ✅ Complete | 49 files |
| **AI Services** | ✅ Complete | 62 files |
| **Documentation** | ✅ Complete | VISION.md, ARCHITECTURE.md, PORTING_REPORT.md |

### 📦 Package Inventory (101 packages)

| Category | Packages | Files |
|----------|----------|-------|
| **Core/Auth** | openric-core, openric-auth, openric-user-manage | 317 |
| **Records/Description** | openric-record-manage, openric-accession, openric-agent-manage | 167 |
| **AI** | openric-ai, openric-ai-governance, openric-ai-services | 118 |
| **GLAM/DAM** | openric-dam, openric-iiif-collection, openric-gallery | 82 |
| **Preservation** | openric-preservation, openric-museum, openric-registry | 244 |
| **Standards** | openric-spectrum, openric-doi-manage, openric-extended-rights | 82 |
| **Research** | openric-research, openric-research-request, openric-semantic-search | 126 |
| **Workflows** | openric-workflow, openric-access-request, openric-feedback | 81 |
| **Operations** | openric-backup, openric-privacy, openric-security-clearance | 135 |
| **Other** | openric-search, openric-reports, openric-settings-manage, etc. | 1,097 |
| **TOTAL** | **101** | **2,449** |

---

## Phase 1: Foundation (✅ Complete)

- [x] Laravel 12 framework setup
- [x] PostgreSQL configuration as default
- [x] Fuseki triplestore integration
- [x] Core package architecture
- [x] Authentication/ACL system
- [x] Basic CRUD on RiC-O entities

---

## Phase 2: Core Archival Functions (✅ Complete)

- [x] Record/Description management (ISAD(G))
- [x] Authority records (ISAAR-CPF)
- [x] Accessioning workflow
- [x] Agent management
- [x] Place/Location management
- [x] Activity/Event tracking
- [x] Repository management (ISDIAH)

---

## Phase 3: AI Integration (✅ Complete)

- [x] NER (Named Entity Recognition)
- [x] Translation services
- [x] Vector search (Qdrant integration)
- [x] AI Governance framework
- [x] Rights matrix for AI
- [x] Provenance logging
- [x] Bias/Harm register
- [x] Evaluation metrics

---

## Phase 4: GLAM/DAM (✅ Complete)

- [x] Digital Asset Management
- [x] IIIF Presentation API 3.0
- [x] IPTC/XMP metadata extraction
- [x] Derivative generation
- [x] Preservation planning
- [x] METS encoding
- [x] Gallery/Exhibition support

---

## Phase 5: Interoperability (In Progress)

- [x] Dublin Core export
- [ ] EAD3 export/import
- [ ] METS/MODS support
- [ ] OAI-PMH endpoint
- [ ] IIIF Manifest generation from RiC-O
- [ ] PREMIS preservation metadata
- [ ] schema.org JSON-LD for SEO

---

## Phase 6: Advanced Features (Planned)

- [ ] RAG (Retrieval-Augmented Generation) for archival research
- [ ] Named Entity Disambiguation (NER + Authority reconciliation)
- [ ] Full-text OCR pipeline
- [ ] Multilingual support (AFRIN local)
- [ ] Timeline visualization
- [ ] Graph exploration UI
- [ ] Batch import wizard
- [ ] API versioning (v1, v2)

---

## Data Layer Architecture

### PostgreSQL (Operational Data)
- Authentication & sessions
- User management & ACL
- Job queues
- Application settings
- Dropdown/cache values
- Audit trail

### Fuseki Triplestore (Archival Data)
- RiC-O 1.1 entities
- Record descriptions
- Agent authority records
- Relationships
- RDF-Star provenance

### OpenSearch (Discovery)
- Full-text search index
- Researcher-facing search
- Faceted navigation

### Qdrant (Semantic Search)
- Vector embeddings
- Similarity search
- AI-assisted discovery

---

## Standards Support

| Standard | Status | Notes |
|----------|--------|-------|
| **RiC-O 1.1** | ✅ Native | Canonical data layer |
| **ISAD(G)** | ✅ View | SPARQL → ISAD(G) rendering |
| **ISAAR-CPF** | ✅ View | Agent records |
| **EAD3** | 🔄 In Progress | Export functional |
| **Dublin Core** | ✅ Export | OAI-PMH ready |
| **METS** | 🔄 In Progress | Structural metadata |
| **PREMIS** | 🔄 In Progress | Preservation metadata |
| **IIIF 3.0** | ✅ Complete | Manifest/Canvas |
| **schema.org** | 🔄 In Progress | Web SEO |
| **RDF-Star** | ✅ Complete | Provenance annotations |

---

## Deployment Targets

- National archives
- University archives
- Municipal archives
- GLAM institutions
- Heritage organizations

**Minimum:** 16GB RAM, 4 cores, SSD  
**Large collections (1M+ records):** 64GB RAM, dedicated Fuseki

---

## Community

- GitHub: github.com/ArchiveHeritageGroup/OpenRiC
- Google Group: Records_in_Contexts_users@googlegroups.com
- ICA EGAD: Official RiC-O standards body

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.3.0 | 2026-03-29 | All 84 Heratio packages ported, 101 total packages |
| 1.2.x | 2026-03-29 | Reports, landing page, feedback enhanced |
| 1.1.x | 2026-03-29 | Registry, AI services, preservation added |
| 1.0.x | 2026-03-28 | Initial framework, core packages |

---

*OpenRiC — RiC-O native, built for the international archival community.*
*Developed by The Archive and Heritage Group — theahg.co.za*
