# OpenRiC Architecture: RiC-First, Then GLAM Layering

**Document version:** 2.0  
**Date:** 2026-03-29  
**Status:** Active Development (v1.3.0)

---

## Executive Summary

OpenRiC implements **RiC-native first**, with GLAM/DAM standards added as interoperability layers. This is a deliberate architectural choice:

1. **RiC-O 1.1 is the canonical data layer** — all entities and relationships stored as RiC-O triples in a triplestore
2. **PostgreSQL for operational data** — authentication, sessions, jobs, settings, audit
3. **GLAM/DAM standards are lenses** — Dublin Core, EAD, METS, MODS, IIIF, PREMIS are rendered from SPARQL queries
4. **RDF-Star provenance** — every triple carries who, when, and basis annotations
5. **Adjunct graphs for specialist metadata** — PREMIS preservation data, IPTC/XMP photo metadata linked to RiC instantiations

This architecture delivers:
- Native RiC-O CRUD with familiar archival UI
- Lossy or lossless exports to traditional standards
- Import normalization retaining source-as-source
- Provenance and rollback capability via RDF-Star
- Complete AI Governance framework

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                           User Interface                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐ │
│  │ ISAD(G) Form │  │ RiC-O Form   │  │ Authority Form    │ │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────┘ │
└─────────┼─────────────────┼─────────────────────┼──────────┘
          │                 │                     │
          ▼                 ▼                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    OpenRiC Application Layer                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           Standards Mapping Service (Core)             │   │
│  │   isadgToRico() │ ricToIsadg() │ eadToRico()       │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           AI Governance Service                         │   │
│  │   isAiAllowed() │ logAiOutput() │ approveOutput()    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
          │                                           │
          ▼                                           ▼
┌─────────────────────┐               ┌─────────────────────────────┐
│    PostgreSQL       │               │      RiC-O Triplestore      │
│  (Operational Data) │               │        (Fuseki 4.x)         │
│  ┌───────────────┐  │               │  ┌───────────────────────┐  │
│  │ Auth/Sessions │  │               │  │  Records, Agents,     │  │
│  │ Users/ACL     │  │               │  │  Activities, Places,  │  │
│  │ Jobs/Queues   │  │               │  │  Instantiations       │  │
│  │ Settings      │  │               │  │  Provenance (RDF-Star)│  │
│  │ Audit Trail   │  │               │  └───────────────────────┘  │
│  └───────────────┘  │               │                             │
│                     │               │  Adjunct Graphs:              │
│  AI Governance:     │               │  • PREMIS preservation      │
│  ┌───────────────┐  │               │  • IPTC/XMP metadata       │
│  │ Rights Matrix │  │               │  • IIIF manifests          │
│  │ Output Log    │  │               └─────────────────────────────┘
│  │ Bias Register │  │                         │
│  │ Metrics       │  │                         ▼
│  └───────────────┘  │               ┌─────────────────────────────┐
└─────────────────────┘               │      Export Layer            │
                                      │  EAD3 │ DC │ IIIF │ METS  │
                                      └─────────────────────────────┘
```

---

## Data Layer Architecture

### PostgreSQL (Operational Data)

**Default database connection:** `pgsql`

| Data Type | Table/Component | Purpose |
|-----------|-----------------|---------|
| Authentication | `users`, `sessions` | User accounts, login sessions |
| Access Control | `permissions`, `roles` | ACL matrix |
| Application Settings | `settings` | Configuration key-value store |
| Dropdowns | `dropdowns` | Form choice values |
| Job Queues | `jobs`, `failed_jobs` | Background processing |
| Audit Trail | `audit_logs` | Change tracking |
| AI Governance | `ai_governance_*` | 8 tables for AI control |

### Fuseki Triplestore (Archival Data)

**Default:** Apache Jena Fuseki 4.x  
**Connection:** Configured via `config/ric.php`

| Graph | Contents |
|-------|----------|
| `ric:Record` | Archival descriptions |
| `ric:RecordSet` | Fonds, series, files |
| `ric:RecordPart` | Items, fragments |
| `ric:Agent` | Persons, corporate bodies, families |
| `ric:Activity` | Events, transactions |
| `ric:Place` | Geographic entities |
| `ric:Instantiation` | Physical/digital carriers |
| `premis:*` | Preservation metadata (adjunct) |
| `iptc:*` | Photo metadata (adjunct) |

---

## Package Architecture (101 packages)

### Core/Foundation
| Package | Files | Description |
|---------|-------|-------------|
| openric-core | 234 | Framework services, helpers |
| openric-auth | 71 | ACL, authentication |
| openric-user-manage | 12 | User administration |

### Records/Description
| Package | Files | Description |
|---------|-------|-------------|
| openric-record-manage | 78 | ISAD(G) descriptions |
| openric-accession | 30 | Accessioning workflow |
| openric-agent-manage | 36 | ISAAR-CPF agents |
| openric-activity-manage | 23 | Events/activities |
| openric-place-manage | 9 | Geographic entities |

### AI Integration
| Package | Files | Description |
|---------|-------|-------------|
| openric-ai | 8 | Basic AI controller |
| openric-ai-services | 62 | NER, translation, embedding |
| openric-ai-governance | 48 | AI rights, provenance, bias |

### GLAM/DAM
| Package | Files | Description |
|---------|-------|-------------|
| openric-dam | 23 | Digital asset management |
| openric-iiif-collection | 29 | IIIF Presentation API 3.0 |
| openric-gallery | 30 | Gallery/exhibition support |
| openric-exhibition | 18 | Exhibition management |

### Preservation
| Package | Files | Description |
|---------|-------|-------------|
| openric-preservation | 28 | Preservation planning |
| openric-museum | 44 | Museum collection management |
| openric-registry | 172 | Authority registry |

### Standards
| Package | Files | Description |
|---------|-------|-------------|
| openric-spectrum | 21 | RAD standard |
| openric-icip | 27 | ICIP standard |
| openric-cdpa | 23 | CDPA standard |
| openric-extended-rights | 34 | Extended rights |
| openric-doi-manage | 17 | DOI management |

### Research
| Package | Files | Description |
|---------|-------|-------------|
| openric-research | 101 | Research requests |
| openric-semantic-search | 15 | Vector/Qdrant search |
| openric-research-request | 5 | Research workflows |

### Operations
| Package | Files | Description |
|---------|-------|-------------|
| openric-backup | 9 | PostgreSQL + Fuseki backup |
| openric-privacy | 49 | GDPR compliance |
| openric-security-clearance | 30 | Clearance levels |
| openric-workflow | 30 | Workflow engine |

### Interoperability
| Package | Files | Description |
|---------|-------|-------------|
| openric-api | 17 | REST API v2 |
| openric-oai | 3 | OAI-PMH endpoint |
| openric-graphql | 4 | GraphQL API |
| openric-metadata-export | 6 | RDF/linked data export |

---

## Core Principles

### Principle 1: RiC-O is Internal Truth

```turtle
@prefix rico: <https://www.ica.org/standards/RiC/ontology#> .
@prefix ex:   <https://ric.theahg.co.za/entity/> .

ex:record-001
  a rico:Record ;
  rico:identifier "AHS-REG-000123" ;
  rico:title "Register of Correspondence, 1989–1992" ;
  rico:scopeAndContent "Incoming and outgoing correspondence..." ;
  rico:hasCreator ex:agent-042 .
```

### Principle 2: GLAM/DAM are Interfaces

Traditional standards are never the canonical store. They are:
- **Views** — exports generated from RiC-O SPARQL queries
- **Import targets** — normalization pipelines that transform external formats into RiC-O

### Principle 3: Adjunct Graphs for Specialist Metadata

```turtle
# RiC instantiation links to PREMIS graph
ex:record-001 rico:hasInstantiation ex:inst-digital-001 .

# PREMIS graph (preservation metadata)
ex:inst-digital-001 premis:hasMessageDigest "sha256:..." .
ex:inst-digital-001 premis:hasFormat premis:fmt/354 .
```

---

## Minimum Viable RiC-O Profile

### Core Datatype Properties

| Property | Usage | Cardinality |
|----------|-------|-------------|
| `rico:identifier` | System/local identifiers | 1..n |
| `rico:title` | Human-readable name | 1..n |
| `rico:scopeAndContent` | Scope and content narrative | 0..n |
| `rico:hasExtent` | Extent statement | 0..n |

### Core Object Properties

| Property | Usage | Cardinality |
|----------|-------|-------------|
| `rico:hasCreator` | Record → Agent | 0..n |
| `rico:hasInstantiation` | Record → Instantiation | 0..n |
| `rico:hasOrHadPart` | Hierarchy | 0..n |
| `rico:isAssociatedWithDate` | Temporal context | 0..n |

---

## AI Governance Framework

OpenRiC includes a complete AI Preparedness Control Framework:

### Tables
| Table | Purpose |
|-------|---------|
| `ai_governance_rights` | AI allowed/restricted per entity |
| `ai_governance_outputs` | AI-generated content provenance |
| `ai_governance_metrics` | Evaluation metrics |
| `ai_governance_bias` | Bias/harm register |
| `ai_governance_readiness` | Corpus completeness |
| `ai_governance_projects` | Deployment checklist |
| `ai_governance_derivatives` | AI derivative packaging |
| `ai_governance_languages` | Multilingual AI controls |

### Key Methods (AiGovernanceService)
```php
isAiAllowed(string $entityIri): bool
logAiOutput(string $iri, string $action, array $data): void
approveOutput(int $outputId): void
addBiasRecord(array $data): void
getDashboardStats(): array
```

---

## GLAM/DAM Mapping Reference

### Dublin Core → RiC-O

| DC Element | RiC-O Target | Notes |
|------------|--------------|-------|
| `dc:identifier` | `rico:identifier` | |
| `dc:title` | `rico:title` | |
| `dc:description` | `rico:scopeAndContent` | |
| `dc:creator` | `rico:hasCreator` → `rico:Agent` | |
| `dc:date` | `rico:isAssociatedWithDate` → `rico:Date` | |

### EAD3 → RiC-O

| EAD Element | RiC-O Target | Notes |
|-------------|--------------|-------|
| `unitid` | `rico:identifier` | |
| `unittitle` | `rico:title` | |
| `unitdate` | `rico:isAssociatedWithDate` | |
| `physdesc` | `rico:hasExtent` | |
| `scopecontent` | `rico:scopeAndContent` | |

### IIIF → RiC-O

| IIIF Resource | RiC-O Target | Notes |
|---------------|--------------|-------|
| Collection | `rico:RecordSet` | Top-level grouping |
| Manifest | `rico:Record` + `rico:hasInstantiation` | Compound object |
| Canvas | `rico:Instantiation` | Page/physical unit |

---

## Provenance and Versioning

OpenRiC uses **RDF-Star** for statement-level provenance:

```turtle
<< ex:record-001 rico:scopeAndContent "Incoming correspondence..." >>
  prov:wasGeneratedBy ex:ai-activity-001 ;
  prov:generatedAtTime "2026-03-29T09:15:00+02:00"^^xsd:dateTime ;
  ex:confidenceScore "0.87"^^xsd:decimal ;
  ex:status "pending" .
```

---

## Standards References

| Standard | Canonical Source | Status |
|----------|-----------------|--------|
| **RiC-O 1.1** | ICA EGAD | ✅ Native |
| **ISAD(G)** | ICA | ✅ View |
| **ISAAR-CPF** | ICA | ✅ View |
| **Dublin Core** | DCMI | ✅ Export |
| **EAD3** | Library of Congress | 🔄 In Progress |
| **METS** | Library of Congress | 🔄 In Progress |
| **PREMIS** | Library of Congress | 🔄 In Progress |
| **IIIF Presentation API 3.0** | IIIF Consortium | ✅ Complete |
| **IPTC Photo Metadata** | IPTC | ✅ DAM support |
| **schema.org** | schema.org | 🔄 In Progress |
| **PROV-O** | W3C | ✅ Complete |

---

## Deployment

**Default Database:** PostgreSQL 15+ (`pgsql`)  
**Default Triplestore:** Apache Jena Fuseki 4.x  
**Search:** OpenSearch 2.x  
**Vector Search:** Qdrant

**Minimum:** 16GB RAM, 4 cores, SSD  
**Large collections (1M+ records):** 64GB RAM, dedicated Fuseki

---

## Governance

OpenRiC is developed by The Archive and Heritage Group and released under **AGPL-3.0**.

**Community Channels:**
- GitHub: github.com/ArchiveHeritageGroup/OpenRiC
- Google Group: Records_in_Contexts_users@googlegroups.com

**Standards:**
- RiC-O 1.1: ica.org/standards/RiC/RiC-O_1-1.html
- RiC-CM 1.0: github.com/ICA-EGAD/RiC-CM

---

*Version 2.0 — Updated 2026-03-29 with full 101-package inventory*
*OpenRiC — RiC-O native, built for the international archival community.*
