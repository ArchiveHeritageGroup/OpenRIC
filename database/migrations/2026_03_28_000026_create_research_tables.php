<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('research_workspace_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('research_workspaces')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->string('entity_type', 100);
            $table->string('title', 1024);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('workspace_id');
            $table->index('entity_iri');
        });

        Schema::create('research_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->string('annotation_type', 50);
            $table->text('content');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('entity_iri');
            $table->index('annotation_type');
        });

        Schema::create('research_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->string('citation_style', 50);
            $table->text('citation_text');
            $table->timestamps();

            $table->index('user_id');
            $table->index('entity_iri');
        });

        Schema::create('research_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->string('assessment_type', 50);
            $table->text('content');
            $table->integer('score')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('entity_iri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_assessments');
        Schema::dropIfExists('research_citations');
        Schema::dropIfExists('research_annotations');
        Schema::dropIfExists('research_workspace_items');
        Schema::dropIfExists('research_workspaces');
    }
};
