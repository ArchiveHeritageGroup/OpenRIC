<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates AI governance tables for OpenRiC:
     * - ai_readiness_profiles: Collection-level AI readiness documentation
     * - ai_rights: Machine-readable AI rights and restrictions
     * - ai_output_log: AI output provenance tracking
     * - ai_evaluation_metrics: AI evaluation metrics storage
     * - bias_harm_register: Bias, harm, and exclusion notes
     * - ai_derivative_profiles: AI derivative packaging profiles
     * - language_ai_settings: Multilingual AI controls
     * - ai_project_readiness: AI project readiness checklists
     */
    public function up(): void
    {
        // AI Readiness Profiles - Collection-level corpus documentation
        Schema::create('ai_readiness_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('collection_iri', 500);
            $table->string('collection_title', 500)->nullable();
            $table->enum('digitization_completeness', ['complete', 'partial', 'not_started'])->default('not_started');
            $table->text('known_gaps')->nullable();
            $table->text('excluded_records')->nullable();
            $table->text('legal_exclusions')->nullable();
            $table->text('privacy_exclusions')->nullable();
            $table->text('representational_bias_notes')->nullable();
            $table->enum('corpus_completeness', ['complete', 'partial', 'sampled', 'biased'])->default('partial');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique('collection_iri');
            $table->index('corpus_completeness');
        });

        // AI Rights & Restrictions Matrix
        Schema::create('ai_rights', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 500);
            $table->string('entity_type', 50)->nullable(); // record, agent, collection
            $table->boolean('ai_allowed')->default(false);
            $table->boolean('summarisation_allowed')->default(false);
            $table->boolean('embedding_allowed')->default(false);
            $table->boolean('training_reuse_allowed')->default(false);
            $table->boolean('redaction_required')->default(false);
            $table->text('ai_review_notes')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index('entity_iri');
            $table->index(['ai_allowed', 'embedding_allowed']);
            $table->index('valid_from');
        });

        // AI Output Provenance Log
        Schema::create('ai_output_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 500);
            $table->enum('action', ['extract', 'summarize', 'translate', 'classify', 'describe']);
            $table->string('model_version', 100)->nullable();
            $table->text('prompt_pipeline')->nullable();
            $table->jsonb('retrieved_records')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->jsonb('risk_flags')->nullable();
            $table->text('output_text')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('approved')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_version')->nullable()->constrained('ai_output_log')->nullOnDelete();
            $table->timestamps();
            
            $table->index('entity_iri');
            $table->index('action');
            $table->index('approved');
            $table->index(['entity_iri', 'action', 'created_at']);
        });

        // AI Evaluation Metrics
        Schema::create('ai_evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 500)->nullable();
            $table->string('project_name', 200)->nullable();
            $table->enum('metric_type', [
                'acceptance_rate', 
                'edit_distance', 
                'precision_recall', 
                'rag_precision', 
                'trust_score',
                'description_quality',
                'translation_accuracy'
            ]);
            $table->decimal('metric_value', 10, 4);
            $table->unsignedInteger('sample_size')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('metric_type');
            $table->index(['metric_type', 'period_start', 'period_end']);
            $table->index('project_name');
        });

        // Bias/Harm/Exclusion Register
        Schema::create('bias_harm_register', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 500)->nullable();
            $table->enum('category', [
                'harmful_language',
                'culturally_sensitive',
                'absent_communities',
                'contested_description',
                'power_imbalance',
                'under_representation',
                'metadata_gap'
            ]);
            $table->text('description');
            $table->text('ai_warning')->nullable();
            $table->text('mitigation_strategy')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('category');
            $table->index('severity');
            $table->index('resolved');
            $table->index('entity_iri');
        });

        // AI Derivative Packaging Profiles
        Schema::create('ai_derivative_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('collection_iri', 500);
            $table->string('profile_name', 200)->default('default');
            $table->boolean('cleaned_ocr_text')->default(false);
            $table->boolean('normalised_metadata_export')->default(false);
            $table->boolean('chunked_retrieval_units')->default(false);
            $table->boolean('redacted_access_copies')->default(false);
            $table->boolean('multilingual_alignment')->default(false);
            $table->jsonb('formats')->default(['pdf', 'txt', 'json']);
            $table->unsignedInteger('chunk_size')->default(512);
            $table->unsignedInteger('chunk_overlap')->default(50);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique('collection_iri');
            $table->index('active');
        });

        // Language AI Settings (Multilingual Control)
        Schema::create('language_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 10); // ISO 639-1
            $table->string('language_name', 100);
            $table->boolean('ai_allowed')->default(true);
            $table->boolean('translation_allowed')->default(true);
            $table->boolean('embedding_enabled')->default(true);
            $table->text('access_warning')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('competency_required')->default(false);
            $table->jsonb('competency_languages')->nullable(); // Languages reviewer can review
            $table->timestamps();
            
            $table->unique('language_code');
            $table->index('ai_allowed');
        });

        // AI Project Readiness Checklists
        Schema::create('ai_project_readiness', function (Blueprint $table) {
            $table->id();
            $table->string('project_name', 200);
            $table->text('use_case_description');
            $table->boolean('corpus_completeness_documented')->default(false);
            $table->boolean('metadata_minimum_met')->default(false);
            $table->boolean('access_rules_structured')->default(false);
            $table->boolean('derivatives_prepared')->default(false);
            $table->boolean('evaluation_plan_approved')->default(false);
            $table->boolean('human_review_workflow_active')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'active'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_project_readiness');
        Schema::dropIfExists('language_ai_settings');
        Schema::dropIfExists('ai_derivative_profiles');
        Schema::dropIfExists('bias_harm_register');
        Schema::dropIfExists('ai_evaluation_metrics');
        Schema::dropIfExists('ai_output_log');
        Schema::dropIfExists('ai_rights');
        Schema::dropIfExists('ai_readiness_profiles');
    }
};
