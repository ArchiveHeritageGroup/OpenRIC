# AI Governance Framework for OpenRiC

**Date:** 2026-03-29  
**Purpose:** Document AI preparedness gaps and outline the AI governance framework needed

## Current State

OpenRiC has basic AI infrastructure:
- ✅ `AiController` with NER extraction, review, summarize, translate actions
- ✅ `AiNerService` for named entity recognition
- ✅ Translation service integration
- ✅ Qdrant vector search integration
- ❌ **Missing**: AI governance, rights matrix, provenance logging, bias documentation

## Missing Modules

### 1. AI Readiness Profile (Collection Level)

**Purpose:** Document whether a corpus is complete, partial, sampled, or biased.

**Required Fields:**
```sql
-- ai_readiness_profiles table
- collection_id (FK)
- digitization_completeness: enum('complete', 'partial', 'not_started')
- known_gaps: text
- excluded_records: text
- legal_exclusions: text
- privacy_exclusions: text
- representational_bias_notes: text
- corpus_completeness: enum('complete', 'partial', 'sampled', 'biased')
- last_reviewed_at: timestamp
- reviewed_by: FK users
```

### 2. AI Rights & Restrictions Matrix

**Purpose:** Machine-readable access conditions for AI systems.

**Required Fields:**
```sql
-- ai_rights table
- entity_iri: varchar (references records/agents)
- ai_allowed: boolean default false
- summarisation_allowed: boolean default false
- embedding_allowed: boolean default false
- training_reuse_allowed: boolean default false
- redaction_required: boolean default false
- ai_review_notes: text
- valid_from: date
- valid_until: date
- created_by: FK users
- created_at: timestamp
```

### 3. AI Output Provenance Log

**Purpose:** Track AI-generated outputs back to source material.

**Required Fields:**
```sql
-- ai_output_log table
- id: bigint PK
- entity_iri: varchar
- action: enum('extract', 'summarize', 'translate', 'classify')
- model_version: varchar
- prompt_pipeline: text
- retrieved_records: jsonb
- confidence_score: decimal
- risk_flags: jsonb
- output_text: text
- reviewed_by: FK users nullable
- reviewed_at: timestamp nullable
- approved: boolean default false
- version: int default 1
- parent_version: FK self nullable
- created_at: timestamp
```

### 4. AI Evaluation Dashboard Data

**Purpose:** Track metrics for AI use cases.

**Required Fields:**
```sql
-- ai_evaluation_metrics table
- id: bigint PK
- entity_iri: varchar
- metric_type: enum('acceptance_rate', 'edit_distance', 'precision_recall', 'rag_precision', 'trust_score')
- metric_value: decimal
- sample_size: int
- period_start: date
- period_end: date
- notes: text
- created_at: timestamp
```

### 5. Bias/Harm/Exclusion Notes

**Purpose:** Document silences, harmful content, and under-representation.

**Required Fields:**
```sql
-- bias_harm_register table
- id: bigint PK
- entity_iri: varchar
- category: enum('harmful_language', 'culturally_sensitive', 'absent_communities', 'contested_description', 'power_imbalance')
- description: text
- ai_warning: text
- mitigation_strategy: text
- severity: enum('low', 'medium', 'high', 'critical')
- reported_by: FK users
- resolved: boolean default false
- resolved_at: timestamp nullable
- resolved_by: FK users nullable
- created_at: timestamp
```

### 6. AI Derivative Packaging Profile

**Purpose:** Define derivatives for AI ingestion.

**Required Fields:**
```sql
-- ai_derivative_profiles table
- collection_id: FK
- cleaned_ocr_text: boolean default false
- normalised_metadata_export: boolean default false
- chunked_retrieval_units: boolean default false
- redacted_access_copies: boolean default false
- multilingual_alignment: boolean default false
- formats: jsonb ['pdf', 'txt', 'json', 'xml']
- chunk_size: int
- chunk_overlap: int
- created_at: timestamp
- updated_at: timestamp
```

### 7. Multilingual AI Control

**Purpose:** Support multilingual collections with language-specific controls.

**Required Fields:**
```sql
-- language_ai_settings table
- language_code: varchar (ISO 639-1)
- ai_allowed: boolean
- translation_allowed: boolean
- embedding_enabled: boolean
- access_warning: text
- reviewer_id: FK users nullable
- competency_required: boolean default false
- created_at: timestamp
- updated_at: timestamp
```

### 8. AI Project Readiness Checklist

**Purpose:** Admin feature to track deployment readiness.

**Required Fields:**
```sql
-- ai_project_readiness table
- id: bigint PK
- project_name: varchar
- use_case_description: text
- corpus_completeness_documented: boolean default false
- metadata_minimum_met: boolean default false
- access_rules_structured: boolean default false
- derivatives_prepared: boolean default false
- evaluation_plan_approved: boolean default false
- human_review_workflow_active: boolean default false
- approved_by: FK users nullable
- approved_at: timestamp nullable
- status: enum('draft', 'pending_approval', 'approved', 'rejected')
- created_by: FK users
- created_at: timestamp
- updated_at: timestamp
```

## Implementation Priority

### Phase 1: Core Infrastructure
1. AI Rights & Restrictions Matrix
2. AI Output Provenance Log

### Phase 2: Collection Readiness
3. AI Readiness Profile
4. AI Derivative Packaging Profile

### Phase 3: Evaluation & Bias
5. AI Evaluation Dashboard Data
6. Bias/Harm/Exclusion Notes

### Phase 4: Multilingual & Admin
7. Multilingual AI Control
8. AI Project Readiness Checklist

## Summary

**Current Status:** OpenRiC has basic AI capabilities (NER, translation, vector search) but lacks the governance layer required for responsible AI use in archival contexts.

**Recommendation:** Implement the 8 missing modules to create a complete AI governance framework that:
- Documents corpus completeness and known gaps
- Enforces machine-readable AI rights
- Tracks AI output provenance
- Enables evaluation and bias tracking
- Supports multilingual collections
- Provides admin visibility into AI readiness

This transforms OpenRiC from "archival operating system" to "AI-ready archival platform."
