<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username', 100)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('action', 50);
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 255)->nullable();
            $table->string('entity_title', 500)->nullable();
            $table->string('module', 100)->nullable();
            $table->string('action_name', 255)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('changed_fields')->nullable();
            $table->string('security_classification', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action', 'idx_audit_action');
            $table->index('entity_type', 'idx_audit_entity_type');
            $table->index('user_id', 'idx_audit_user');
            $table->index('created_at', 'idx_audit_created');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
