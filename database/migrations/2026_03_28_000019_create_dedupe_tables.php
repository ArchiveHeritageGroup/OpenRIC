<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create deduplication tables for the openric-dedupe package.
 *
 * Adapted from Heratio's ahg_duplicate_detection, ahg_duplicate_rule,
 * and ahg_dedupe_scan tables. Heratio uses 3 tables with MySQL-specific
 * features and AtoM user references.
 *
 * OpenRiC consolidates into a single duplicate_candidates table storing
 * IRI pairs with SPARQL-detected similarity scores. Resolution tracking
 * uses PostgreSQL jsonb for flexible match field storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('entity_a_iri', 2048);
            $table->string('entity_b_iri', 2048);
            $table->string('entity_type', 50)
                ->comment('RecordSet, Agent, Place, etc.');
            $table->decimal('similarity_score', 5, 4)
                ->comment('0.0000 to 1.0000 composite similarity score');
            $table->jsonb('match_fields')
                ->comment('JSON object with field-level match details');
            $table->string('status', 20)->default('pending')
                ->comment('pending, merged, not_duplicate');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Unique constraint on the IRI pair to prevent duplicate entries
            $table->unique(['entity_a_iri', 'entity_b_iri'], 'dedupe_pair_unique');

            $table->index('status');
            $table->index('entity_type');
            $table->index('similarity_score');
            $table->index('resolved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_candidates');
    }
};
