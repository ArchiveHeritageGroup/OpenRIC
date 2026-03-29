<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create translation tables for the openric-translation package.
 *
 * Adapted from Heratio ahg-translation which used:
 * - ahg_translation_draft (MySQL with object_id FK)
 * - ahg_translation_log (MySQL)
 * - ahg_translation_settings (MySQL with ON DUPLICATE KEY)
 *
 * OpenRiC uses entity_iri (RiC IRI) instead of object_id, PostgreSQL
 * for all upserts, and adds source_hash for deduplication.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── translation_drafts ──────────────────────────────────────────
        // Stores machine-translated text before approval.
        // Status flow: draft -> applied | rejected | superseded
        Schema::create('translation_drafts', function (Blueprint $table): void {
            $table->id();
            $table->text('entity_iri')->index();
            $table->string('source_culture', 16);
            $table->string('target_culture', 16);
            $table->string('field_name', 100);
            $table->string('source_hash', 64)->comment('SHA-256 of source_text for deduplication');
            $table->text('source_text');
            $table->text('translated_text');
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft, applied, rejected, superseded');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            // Composite index for deduplication lookup
            $table->index(
                ['entity_iri', 'field_name', 'source_culture', 'target_culture', 'source_hash'],
                'idx_translation_drafts_dedup'
            );

            // Index for listing drafts by entity + status
            $table->index(['entity_iri', 'status'], 'idx_translation_drafts_entity_status');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // ── translation_log ─────────────────────────────────────────────
        // Audit log of every translation attempt (success and failure).
        Schema::create('translation_log', function (Blueprint $table): void {
            $table->id();
            $table->text('entity_iri')->index();
            $table->string('source_culture', 16);
            $table->string('target_culture', 16);
            $table->unsignedInteger('field_count')->default(1);
            $table->string('status', 20)->default('success')->index()
                ->comment('success, error, partial');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_iri', 'created_at'], 'idx_translation_log_entity_time');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // ── translation_settings ────────────────────────────────────────
        // Key/value store for translation configuration (endpoint, API key, etc.).
        Schema::create('translation_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('setting_key', 191)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_drafts');
        Schema::dropIfExists('translation_log');
        Schema::dropIfExists('translation_settings');
    }
};
