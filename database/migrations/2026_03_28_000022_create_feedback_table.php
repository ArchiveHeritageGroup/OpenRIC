<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 1024);
            $table->string('category', 50)->default('general');
            $table->text('message');
            $table->integer('rating')->nullable();
            $table->string('url', 2048)->nullable()->comment('URL/slug of related record');
            $table->string('feed_name', 100)->default('');
            $table->string('feed_surname', 100)->default('');
            $table->string('feed_email', 255)->default('');
            $table->string('feed_phone', 50)->default('');
            $table->string('feed_relationship', 255)->default('');
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
