<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Module 1: AI Readiness Profiles ─────────────────────────
        Schema::create('ai_readiness_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('collection_iri', 1000);
            $table->string('collection_title', 500)->default('');
            $table->string('digitisation_completeness', 50)->default('unknown'); // complete, partial, sampled, none, unknown
            $table->string('corpus_status', 50)->default('unknown'); // complete, partial, sampled
            $table->text('known_gaps')->nullable();
            $table->text('excluded_records')->nullable();
            $table->text('legal_privacy_exclusions')->nullable();
            $table->text('representational_bias_notes')->nullable();
            $table->text('data_quality_notes')->nullable();
            $table->text('format_notes')->nullable(); // file formats, encoding, structure
            $table->jsonb('languages_present')->nullable(); // ["en","af","zu"]
            $table->jsonb('metadata_standards')->nullable(); // ["ISAD(G)","Dublin Core"]
            $table->integer('estimated_item_count')->nullable();
            $table->integer('digitised_item_count')->nullable();
            $table->integer('described_item_count')->nullable();
            $table->timestamp('last_assessed_at')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->timestamps();

            $table->index('collection_iri');
        });

        // ── Module 2: AI Rights & Restrictions Matrix ───────────────
        Schema::create('ai_rights_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('applies_to_iri', 1000); // entity or collection IRI
            $table->string('restriction_scope', 50)->default('entity'); // entity, collection, global
            $table->boolean('ai_allowed')->default(true);
            $table->boolean('summarisation_allowed')->default(true);
            $table->boolean('embedding_indexing_allowed')->default(true);
            $table->boolean('training_reuse_allowed')->default(false);
            $table->boolean('redaction_required_before_ai')->default(false);
            $table->boolean('rag_retrieval_allowed')->default(true);
            $table->boolean('translation_allowed')->default(true);
            $table->boolean('sensitivity_scan_allowed')->default(true);
            $table->text('restriction_notes')->nullable();
            $table->string('legal_basis', 255)->nullable(); // POPIA, GDPR, donor agreement, etc.
            $table->date('restriction_expires_at')->nullable();
            $table->unsignedBigInteger('set_by')->nullable();
            $table->timestamps();

            $table->index('applies_to_iri');
            $table->index('restriction_scope');
        });

        // ── Module 3: AI Output Provenance Log ──────────────────────
        Schema::create('ai_output_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 1000);
            $table->string('output_type', 100); // description_draft, sensitivity_flag, keyword_suggestion, summary, embedding, rag_response, classification, translation
            $table->string('model_name', 255); // claude-sonnet-4-20250514, nomic-embed-text, nllb-200
            $table->string('model_version', 100)->nullable();
            $table->string('pipeline_name', 255)->nullable(); // which prompt/pipeline
            $table->text('prompt_template')->nullable();
            $table->text('input_text')->nullable(); // what was sent to the model
            $table->text('raw_output')->nullable(); // the AI output
            $table->text('approved_output')->nullable(); // human-reviewed version
            $table->jsonb('retrieved_records')->nullable(); // IRIs used for RAG
            $table->integer('retrieved_record_count')->default(0);
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000 - 1.0000
            $table->jsonb('risk_flags')->nullable(); // ["low_confidence","sensitive_content"]
            $table->string('status', 50)->default('pending_review'); // pending_review, approved, rejected, auto_applied, superseded
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->integer('edit_distance')->nullable(); // Levenshtein between raw and approved
            $table->integer('processing_time_ms')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('output_type');
            $table->index('status');
            $table->index('model_name');
            $table->index('created_at');
        });

        // ── Module 4: AI Evaluation Metrics ─────────────────────────
        Schema::create('ai_evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('use_case', 100); // description, sensitivity, rag, embedding, classification, translation
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_outputs')->default(0);
            $table->integer('approved_outputs')->default(0);
            $table->integer('rejected_outputs')->default(0);
            $table->integer('pending_outputs')->default(0);
            $table->decimal('acceptance_rate', 5, 2)->nullable(); // percentage
            $table->decimal('average_edit_distance', 10, 2)->nullable();
            $table->decimal('average_confidence', 5, 4)->nullable();
            $table->integer('staff_time_saved_seconds')->nullable();
            $table->decimal('sensitivity_true_positives', 10, 2)->nullable();
            $table->decimal('sensitivity_false_positives', 10, 2)->nullable();
            $table->decimal('sensitivity_precision', 5, 4)->nullable();
            $table->decimal('sensitivity_recall', 5, 4)->nullable();
            $table->decimal('rag_retrieval_precision', 5, 4)->nullable();
            $table->decimal('user_satisfaction_avg', 3, 2)->nullable(); // 1.00-5.00
            $table->integer('user_satisfaction_count')->default(0);
            $table->decimal('traceability_score', 5, 4)->nullable(); // % linked to source
            $table->jsonb('model_breakdown')->nullable(); // per-model stats
            $table->unsignedBigInteger('computed_by')->nullable();
            $table->timestamps();

            $table->index(['use_case', 'period_start']);
        });

        // ── Module 4b: User satisfaction ratings ────────────────────
        Schema::create('ai_satisfaction_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_output_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('ai_output_id')->references('id')->on('ai_output_log')->cascadeOnDelete();
            $table->unique(['ai_output_id', 'user_id']);
        });

        // ── Module 5: Bias, Harm & Exclusion Register ───────────────
        Schema::create('ai_bias_register', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 1000)->nullable(); // null = collection-level
            $table->string('collection_iri', 1000)->nullable();
            $table->string('risk_type', 100); // harmful_language, culturally_sensitive, absent_community, contested_description, colonial_bias, gender_bias, racial_bias, ageism, ableism, religious_bias, other
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->text('description');
            $table->text('specific_content')->nullable(); // the actual harmful text/content
            $table->text('mitigation_notes')->nullable();
            $table->text('ai_warning')->nullable(); // warning for downstream AI use
            $table->boolean('requires_redaction')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->unsignedBigInteger('flagged_by')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('collection_iri');
            $table->index('risk_type');
            $table->index('severity');
        });

        // ── Module 6: AI Derivative Packaging ───────────────────────
        Schema::create('ai_derivatives', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 1000);
            $table->string('derivative_type', 100); // ocr_text, normalized_metadata, chunked_retrieval, redacted_copy, multilingual_alignment, cleaned_text, structured_extract
            $table->string('format', 50); // utf8_text, json, xml, csv, turtle, jsonld, pdf
            $table->string('file_path', 1000);
            $table->bigInteger('file_size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('source_file_path', 1000)->nullable();
            $table->string('source_checksum_sha256', 64)->nullable();
            $table->text('processing_notes')->nullable();
            $table->string('processing_tool', 255)->nullable(); // tesseract, tika, custom
            $table->string('language', 10)->nullable();
            $table->boolean('is_current')->default(true);
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('derivative_type');
            $table->index('is_current');
        });

        // ── Module 7: Multilingual AI Control ───────────────────────
        Schema::create('ai_language_config', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 10); // en, af, zu, xh, etc.
            $table->string('language_name', 100);
            $table->boolean('embedding_enabled')->default(false);
            $table->boolean('translation_enabled')->default(false);
            $table->boolean('rag_enabled')->default(false);
            $table->boolean('ocr_enabled')->default(false);
            $table->boolean('sensitivity_scan_enabled')->default(false);
            $table->string('embedding_model', 255)->nullable(); // model used for this language
            $table->string('translation_model', 255)->nullable();
            $table->text('access_warnings')->nullable(); // language-specific warnings
            $table->jsonb('reviewer_user_ids')->nullable(); // users competent in this language
            $table->timestamps();

            $table->unique('language_code');
        });

        // ── Module 8: AI Project Readiness Checklist ────────────────
        Schema::create('ai_readiness_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('project_name', 255);
            $table->text('project_description')->nullable();
            $table->string('use_case', 255); // what AI use case this checklist is for
            $table->string('checklist_status', 50)->default('not_started'); // not_started, in_progress, ready, blocked
            // The 7 checklist items
            $table->boolean('use_case_defined')->default(false);
            $table->text('use_case_notes')->nullable();
            $table->boolean('corpus_completeness_documented')->default(false);
            $table->unsignedBigInteger('readiness_profile_id')->nullable(); // link to ai_readiness_profiles
            $table->boolean('metadata_minimum_met')->default(false);
            $table->text('metadata_notes')->nullable();
            $table->boolean('access_rules_structured')->default(false);
            $table->text('access_rules_notes')->nullable();
            $table->boolean('derivatives_prepared')->default(false);
            $table->text('derivatives_notes')->nullable();
            $table->boolean('evaluation_plan_approved')->default(false);
            $table->text('evaluation_plan')->nullable();
            $table->boolean('human_review_workflow_active')->default(false);
            $table->text('workflow_notes')->nullable();
            // Approval
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('checklist_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_readiness_checklists');
        Schema::dropIfExists('ai_language_config');
        Schema::dropIfExists('ai_derivatives');
        Schema::dropIfExists('ai_bias_register');
        Schema::dropIfExists('ai_satisfaction_ratings');
        Schema::dropIfExists('ai_evaluation_metrics');
        Schema::dropIfExists('ai_output_log');
        Schema::dropIfExists('ai_rights_restrictions');
        Schema::dropIfExists('ai_readiness_profiles');
    }
};
