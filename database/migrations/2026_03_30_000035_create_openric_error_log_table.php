<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openric_error_log', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error')->index();
            $table->text('message');
            $table->text('url')->nullable();
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('trace')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openric_error_log');
    }
};
