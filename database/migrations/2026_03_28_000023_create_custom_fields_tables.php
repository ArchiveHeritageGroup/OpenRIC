<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('label', 500);
            $table->string('field_type', 50)->comment('text/textarea/number/date/select/checkbox/url');
            $table->string('entity_type', 100);
            $table->jsonb('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('entity_type');
            $table->index('is_active');
        });

        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('field_id')->constrained('custom_field_definitions')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['field_id', 'entity_iri']);
            $table->index('entity_iri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
    }
};
