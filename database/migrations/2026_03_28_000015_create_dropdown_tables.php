<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dropdown/taxonomy tables — adapted from Heratio ahg-core.
 *
 * Provides controlled vocabulary management for form fields.
 * In OpenRiC, these map to RiC-O / SKOS concepts but are cached
 * in PostgreSQL for fast form rendering and validation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dropdown values — controlled vocabulary terms
        Schema::create('dropdowns', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy', 100)->comment('Vocabulary name: level_of_description, status, etc.');
            $table->string('code', 100)->comment('Machine-readable code');
            $table->string('label', 500)->comment('Human-readable display label');
            $table->string('color', 7)->nullable()->comment('Hex color for badges');
            $table->string('icon', 100)->nullable()->comment('Icon class name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('skos_iri', 2048)->nullable()->comment('Optional SKOS concept IRI in Fuseki');
            $table->jsonb('metadata')->nullable()->comment('Additional attributes');
            $table->timestamps();

            $table->unique(['taxonomy', 'code'], 'uq_dropdown_taxonomy_code');
            $table->index('taxonomy', 'idx_dropdown_taxonomy');
            $table->index('is_active', 'idx_dropdown_active');
        });

        // Column-to-taxonomy mappings — which form fields use which dropdown
        Schema::create('dropdown_column_map', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100)->comment('OpenRiC entity: record, agent, place, etc.');
            $table->string('field_name', 255)->comment('Form field name or RiC-O property');
            $table->string('taxonomy', 100)->comment('References dropdowns.taxonomy');
            $table->boolean('is_strict')->default(true)->comment('If true, only dropdown values allowed');
            $table->timestamps();

            $table->unique(['entity_type', 'field_name'], 'uq_dropdown_map');
            $table->index('taxonomy', 'idx_dropdown_map_taxonomy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropdown_column_map');
        Schema::dropIfExists('dropdowns');
    }
};
