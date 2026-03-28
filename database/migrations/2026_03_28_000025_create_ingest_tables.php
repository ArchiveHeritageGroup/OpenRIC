<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingest_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('filename', 500);
            $table->string('format', 50);
            $table->string('status', 50)->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->jsonb('error_log')->nullable();
            $table->jsonb('column_mapping')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('format');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingest_jobs');
    }
};
