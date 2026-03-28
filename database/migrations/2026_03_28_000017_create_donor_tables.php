<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create donor tables for the openric-donor package.
 *
 * Adapted from Heratio's AtoM donor/actor/actor_i18n/contact_information schema.
 * Heratio stores donors across 6+ tables: object, actor, donor, actor_i18n,
 * slug, contact_information, contact_information_i18n.
 * OpenRiC consolidates into a single donors table with soft deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 1024);
            $table->string('contact_person', 255)->nullable();
            $table->string('institution', 1024)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 1024)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('donor_type', 50)->default('individual')
                ->comment('individual, organization, estate, government');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('donor_type');
            $table->index('is_active');
            $table->index('name');
            $table->index('created_by');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
