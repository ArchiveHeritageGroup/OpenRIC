<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI services tables — adapted from Heratio ahg-ai-services (6,423 lines).
 *
 * Tables:
 *   - llm_configs: LLM provider configurations (OpenAI, Anthropic, Ollama)
 *   - ner_entities: Named entity extraction results and review workflow
 *   - ai_suggestions: AI-generated description suggestions
 *   - ai_jobs: Batch AI processing jobs
 */
return new class extends Migration
{
    public function up(): void
    {
        // LLM provider configurations — from Heratio ahg_llm_config
        Schema::create('llm_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('provider', 50)->comment('ollama, openai, anthropic');
            $table->string('model', 255)->comment('Model name: llama3, gpt-4o, claude-sonnet-4-20250514, etc.');
            $table->string('endpoint_url', 2048)->nullable()->comment('API endpoint URL (required for Ollama, optional for cloud)');
            $table->text('api_key_encrypted')->nullable()->comment('AES-256-CBC encrypted API key');
            $table->integer('max_tokens')->default(2000);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('timeout_seconds')->default(120);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('options')->nullable()->comment('Provider-specific options');
            $table->timestamps();

            $table->index('provider', 'idx_llm_provider');
            $table->index('is_active', 'idx_llm_active');
        });

        // NER entity extraction results — from Heratio ahg_ner_entity
        Schema::create('ner_entities', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048)->comment('IRI of the source record');
            $table->string('entity_type', 50)->comment('person, organization, place, date, subject');
            $table->string('text', 1000)->comment('Extracted entity text');
            $table->string('normalized_text', 1000)->nullable()->comment('Normalized/cleaned version');
            $table->integer('start_offset')->nullable()->comment('Character offset in source text');
            $table->integer('end_offset')->nullable();
            $table->decimal('confidence', 5, 4)->nullable()->comment('Extraction confidence 0.0-1.0');
            $table->string('source', 50)->default('llm')->comment('llm, api, manual');
            $table->string('status', 50)->default('pending')->comment('pending, linked, approved, rejected');
            $table->string('linked_iri', 2048)->nullable()->comment('IRI of the linked authority/term');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('entity_iri', 'idx_ner_entity_iri');
            $table->index('status', 'idx_ner_status');
            $table->index('entity_type', 'idx_ner_type');
        });

        // AI-generated description suggestions — from Heratio ahg_description_suggestion
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048)->comment('IRI of the target record');
            $table->string('field_name', 255)->comment('RiC-O property being suggested');
            $table->text('original_text')->nullable()->comment('Original field value');
            $table->text('suggested_text')->comment('AI-generated suggestion');
            $table->string('suggestion_type', 50)->comment('description, summary, translation, spellcheck');
            $table->string('model_used', 255)->nullable()->comment('Model that generated this');
            $table->integer('generation_time_ms')->nullable();
            $table->string('status', 50)->default('pending')->comment('pending, accepted, rejected, edited');
            $table->text('applied_text')->nullable()->comment('Final text if edited before accepting');
            $table->foreignId('generated_for')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('entity_iri', 'idx_ai_sugg_iri');
            $table->index('status', 'idx_ai_sugg_status');
            $table->index('suggestion_type', 'idx_ai_sugg_type');
        });

        // Batch AI processing jobs
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type', 50)->comment('ner_extract, translate, summarize, spellcheck, htr, embed');
            $table->string('status', 50)->default('pending')->comment('pending, processing, completed, failed');
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->jsonb('parameters')->nullable()->comment('Job configuration');
            $table->jsonb('results')->nullable()->comment('Job results summary');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('job_type', 'idx_ai_job_type');
            $table->index('status', 'idx_ai_job_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ner_entities');
        Schema::dropIfExists('llm_configs');
    }
};
