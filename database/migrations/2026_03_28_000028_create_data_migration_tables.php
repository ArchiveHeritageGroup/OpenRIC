<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_migration_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('source_file', 500)->nullable();
            $table->string('source_format', 50)->default('csv');
            $table->string('target_entity_type', 100);
            $table->jsonb('column_mapping')->default('{}');
            $table->jsonb('transform_rules')->default('{}');
            $table->string('status', 50)->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->jsonb('error_log')->default('[]');
            $table->jsonb('rollback_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });

        Schema::create('data_migration_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('entity_type', 100);
            $table->jsonb('column_mapping')->default('{}');
            $table->jsonb('transform_rules')->default('{}');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_migration_presets');
        Schema::dropIfExists('data_migration_jobs');
    }
};
