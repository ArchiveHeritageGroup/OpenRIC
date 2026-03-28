<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create accession tables for the openric-accession package.
 *
 * Adapted from Heratio's AtoM accession/accession_i18n/deaccession schema.
 * Heratio stores accessions across 5+ tables with i18n support and relation-based
 * donor/record links. OpenRiC consolidates into 2 tables with direct FK references
 * and IRI-based record linking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessions', function (Blueprint $table) {
            $table->id();
            $table->string('accession_number', 255)->unique();
            $table->string('title', 1024)->nullable();
            $table->unsignedBigInteger('donor_id')->nullable();
            $table->date('received_date')->nullable();
            $table->text('description')->nullable();
            $table->string('extent', 1024)->nullable();
            $table->text('condition_notes')->nullable();
            $table->text('access_restrictions')->nullable();
            $table->string('processing_status', 50)->default('pending')
                ->comment('pending, in_progress, processed, archived');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('object_iri', 2048)->nullable()
                ->comment('IRI of the linked archival record in Fuseki');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('donor_id')
                ->references('id')
                ->on('donors')
                ->onDelete('set null');
            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('processing_status');
            $table->index('received_date');
            $table->index('donor_id');
            $table->index('created_by');
        });

        Schema::create('accession_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accession_id');
            $table->string('object_iri', 2048)->nullable()
                ->comment('IRI of the linked archival record in Fuseki');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('accession_id')
                ->references('id')
                ->on('accessions')
                ->onDelete('cascade');

            $table->index('accession_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accession_items');
        Schema::dropIfExists('accessions');
    }
};
