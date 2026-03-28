<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exhibitions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->string('slug', 255)->unique();
            $table->string('exhibition_type', 50)->default('temporary');
            $table->string('project_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('theme', 255)->nullable();
            $table->string('target_audience', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('venue', 255)->nullable();
            $table->string('status', 50)->default('planning');
            $table->string('curator', 255)->nullable();
            $table->string('designer', 255)->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('budget_currency', 10)->default('ZAR');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('exhibition_type');
            $table->index('start_date');
        });

        Schema::create('exhibition_objects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id');
            $table->string('entity_iri', 1000)->default('');
            $table->string('entity_type', 50)->default('Record');
            $table->string('title', 500)->default('');
            $table->string('identifier', 255)->default('');
            $table->string('section', 255)->default('');
            $table->string('status', 50)->default('pending');
            $table->text('notes')->nullable();
            $table->string('thumbnail_url', 1000)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->cascadeOnDelete();
            $table->index('exhibition_id');
        });

        Schema::create('exhibition_storylines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->cascadeOnDelete();
            $table->index('exhibition_id');
        });

        Schema::create('exhibition_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('location', 255)->default('');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->cascadeOnDelete();
            $table->index('exhibition_id');
        });

        Schema::create('exhibition_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_time', 10)->nullable();
            $table->string('location', 255)->default('');
            $table->string('event_type', 50)->default('general');
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->cascadeOnDelete();
            $table->index(['exhibition_id', 'event_date']);
        });

        Schema::create('exhibition_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id');
            $table->string('title', 255);
            $table->string('category', 100)->default('general');
            $table->boolean('is_completed')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->cascadeOnDelete();
            $table->index(['exhibition_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exhibition_checklists');
        Schema::dropIfExists('exhibition_events');
        Schema::dropIfExists('exhibition_sections');
        Schema::dropIfExists('exhibition_storylines');
        Schema::dropIfExists('exhibition_objects');
        Schema::dropIfExists('exhibitions');
    }
};
