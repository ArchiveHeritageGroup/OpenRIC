<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow notifications, queue, and publish gate tables.
 * Adapted from Heratio ahg-workflow package (841 lines).
 *
 * Adds:
 *   - Task notifications (email/in-app alerts for assignments, SLA warnings)
 *   - Workflow queue (pending task processing)
 *   - Publish gate rules (validation checks before publication)
 *   - Publish gate results (per-object gate evaluation)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Workflow notifications — alerts for task assignment, SLA breach, decision
        Schema::create('workflow_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('workflow_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50)->comment('assigned, sla_warning, sla_breach, decision, reminder');
            $table->string('channel', 50)->default('database')->comment('database, email, both');
            $table->string('subject', 500);
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read'], 'idx_wf_notif_user_read');
            $table->index('type', 'idx_wf_notif_type');
        });

        // Publish gate rules — validation rules that must pass before an object can be published
        Schema::create('publish_gate_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('rule_type', 50)->comment('required_fields, classification, workflow, custom');
            $table->jsonb('rule_config')->comment('Rule parameters: required fields, min classification, etc.');
            $table->string('entity_type', 100)->nullable()->comment('NULL = applies to all entity types');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('entity_type', 'idx_pub_gate_entity');
            $table->index('is_active', 'idx_pub_gate_active');
        });

        // Publish gate results — per-object evaluation of gate rules
        Schema::create('publish_gate_results', function (Blueprint $table) {
            $table->id();
            $table->string('object_iri', 2048);
            $table->foreignId('rule_id')->constrained('publish_gate_rules')->cascadeOnDelete();
            $table->boolean('passed')->default(false);
            $table->text('failure_reason')->nullable();
            $table->jsonb('evaluation_data')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamp('evaluated_at')->useCurrent();

            $table->index('object_iri', 'idx_pub_gate_result_iri');
            $table->index('passed', 'idx_pub_gate_result_passed');
        });

        // SLA policies — define response/resolution times for workflow tasks
        Schema::create('workflow_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('warning_hours')->comment('Hours before due date to send warning');
            $table->integer('breach_hours')->comment('Hours after due date to escalate');
            $table->string('escalation_action', 50)->default('notify')->comment('notify, reassign, escalate');
            $table->foreignId('escalation_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link SLA policies to workflow steps
        if (Schema::hasColumn('workflow_steps', 'id')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                if (!Schema::hasColumn('workflow_steps', 'sla_policy_id')) {
                    $table->foreignId('sla_policy_id')->nullable()->after('is_active')
                        ->constrained('workflow_sla_policies')->nullOnDelete();
                }
                if (!Schema::hasColumn('workflow_steps', 'default_due_days')) {
                    $table->integer('default_due_days')->nullable()->after('sla_policy_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workflow_steps', 'sla_policy_id')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->dropForeign(['sla_policy_id']);
                $table->dropColumn(['sla_policy_id', 'default_due_days']);
            });
        }

        Schema::dropIfExists('workflow_sla_policies');
        Schema::dropIfExists('publish_gate_results');
        Schema::dropIfExists('publish_gate_rules');
        Schema::dropIfExists('workflow_notifications');
    }
};
