<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create statistics tables: usage_events, statistics_daily, statistics_monthly, bot_patterns.
 *
 * Adapted from Heratio ahg-statistics schema (ahg_usage_event, ahg_statistics_daily, ahg_statistics_monthly, ahg_bot_list).
 * PostgreSQL-native: entity references use IRIs not MySQL IDs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────
        // Raw usage events
        // ──────────────────────────────────────────
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048)->comment('IRI of the entity viewed/downloaded/searched');
            $table->string('entity_type', 100)->default('Record')->comment('RiC-O type: Record, Instantiation, etc.');
            $table->string('event_type', 20)->comment('view, download, search');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Authenticated user, if any');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6, possibly anonymized');
            $table->string('user_agent', 512)->nullable();
            $table->string('country', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            $table->string('city', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes for common queries
            $table->index('event_type');
            $table->index('entity_iri');
            $table->index('created_at');
            $table->index('country');
            $table->index(['event_type', 'created_at']);
            $table->index(['entity_iri', 'event_type', 'created_at']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ──────────────────────────────────────────
        // Daily aggregated statistics
        // ──────────────────────────────────────────
        Schema::create('statistics_daily', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048);
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);

            $table->unique(['entity_iri', 'date']);
            $table->index('date');
            $table->index(['entity_iri', 'date']);
        });

        // ──────────────────────────────────────────
        // Monthly aggregated statistics
        // ──────────────────────────────────────────
        Schema::create('statistics_monthly', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048);
            $table->string('year_month', 7)->comment('Format: YYYY-MM');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);

            $table->unique(['entity_iri', 'year_month']);
            $table->index('year_month');
            $table->index(['entity_iri', 'year_month']);
        });

        // ──────────────────────────────────────────
        // Bot patterns for filtering
        // ──────────────────────────────────────────
        Schema::create('bot_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('pattern', 512)->comment('Substring matched against user-agent with ILIKE');
            $table->string('category', 100)->default('general')->comment('search_engine, scraper, monitor, general');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_patterns');
        Schema::dropIfExists('statistics_monthly');
        Schema::dropIfExists('statistics_daily');
        Schema::dropIfExists('usage_events');
    }
};
