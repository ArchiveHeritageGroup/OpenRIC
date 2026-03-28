<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('object_iri', 2048);
            $table->foreignId('assessed_by')->constrained('users');
            $table->timestamp('assessed_at')->useCurrent();
            $table->string('condition_code', 50);
            $table->string('condition_label', 255);
            $table->integer('conservation_priority')->default(0);
            $table->integer('completeness_pct')->default(100);
            $table->jsonb('hazards')->nullable();
            $table->text('storage_requirements')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_assessment_date')->nullable();
            $table->timestamps();
            $table->index('object_iri', 'idx_cond_iri');
        });

        Schema::create('condition_assessment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('condition_assessments')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users');
            $table->string('field_changed', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_assessment_history');
        Schema::dropIfExists('condition_assessments');
    }
};
