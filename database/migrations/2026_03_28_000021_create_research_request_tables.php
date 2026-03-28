<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_iri', 2048);
            $table->string('entity_type', 100);
            $table->string('title', 500);
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'entity_iri']);
            $table->index('user_id');
        });

        Schema::create('research_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('purpose');
            $table->string('status', 50)->default('pending');
            $table->jsonb('items');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_requests');
        Schema::dropIfExists('research_cart_items');
    }
};
