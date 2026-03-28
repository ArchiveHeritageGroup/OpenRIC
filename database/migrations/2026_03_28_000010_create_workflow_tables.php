<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('step_type', 50)->default('approval');
            $table->integer('step_order');
            $table->text('instructions')->nullable();
            $table->text('action_required')->nullable();
            $table->jsonb('checklist')->nullable();
            $table->boolean('pool_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['workflow_id', 'step_order'], 'idx_wf_step_order');
        });

        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows');
            $table->foreignId('workflow_step_id')->constrained('workflow_steps');
            $table->string('object_iri', 2048)->nullable();
            $table->string('object_type', 100)->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('decision', 50)->default('pending');
            $table->integer('priority')->default(0);
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('claimed_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('decision_comment')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index('status', 'idx_wf_task_status');
            $table->index('assigned_to', 'idx_wf_task_assigned');
        });

        Schema::create('workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('workflow_tasks');
            $table->foreignId('workflow_id')->constrained('workflows');
            $table->unsignedBigInteger('workflow_step_id');
            $table->string('action', 50);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('performed_by')->constrained('users');
            $table->text('comment')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->index('task_id', 'idx_wf_hist_task');
            $table->index('performed_at', 'idx_wf_hist_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_tasks');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
