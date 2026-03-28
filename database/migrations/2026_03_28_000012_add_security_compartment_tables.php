<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security compartment and access control tables.
 * Adapted from Heratio ahg-security-clearance package (867 lines).
 *
 * Compartments provide fine-grained access beyond hierarchical classification:
 *   - A user with "Secret" clearance still needs compartment access for restricted groups
 *   - Access requests create an approval workflow
 *   - Declassification can be scheduled with automatic downgrade
 *   - All access grants are logged for compliance
 */
return new class extends Migration
{
    public function up(): void
    {
        // Security compartments — "Eyes Only", regional divisions, project groups
        Schema::create('security_compartments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('security_compartments')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active', 'idx_sec_comp_active');
        });

        // User compartment access — which compartments a user can access
        Schema::create('user_compartment_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('compartment_id')->constrained('security_compartments')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'compartment_id'], 'uq_user_compartment');
        });

        // Object compartment restrictions — which compartments an object belongs to
        Schema::create('object_compartment_access', function (Blueprint $table) {
            $table->id();
            $table->string('object_iri', 2048);
            $table->foreignId('compartment_id')->constrained('security_compartments')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->index('object_iri', 'idx_obj_comp_iri');
        });

        // Security access requests — temporary or elevated access workflow
        Schema::create('security_access_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('object_iri', 2048)->nullable();
            $table->foreignId('classification_id')->nullable()->constrained('security_classifications');
            $table->foreignId('compartment_id')->nullable()->constrained('security_compartments');
            $table->string('request_type', 50)->comment('view, download, print, declassify, elevate');
            $table->text('justification');
            $table->string('status', 50)->default('pending')->comment('pending, approved, denied, expired');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_sec_req_user');
            $table->index('status', 'idx_sec_req_status');
            $table->index('object_iri', 'idx_sec_req_iri');
        });

        // Direct object access grants — approved temporary access to specific objects
        Schema::create('object_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('object_iri', 2048);
            $table->string('access_type', 50)->comment('view, download, print');
            $table->foreignId('granted_by')->constrained('users');
            $table->foreignId('request_id')->nullable()->constrained('security_access_requests')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'object_iri'], 'idx_obj_grant_user_iri');
            $table->index('is_active', 'idx_obj_grant_active');
        });

        // Object declassification schedule — automatic downgrade of classification
        Schema::create('object_declassification_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('object_iri', 2048);
            $table->foreignId('current_classification_id')->constrained('security_classifications');
            $table->foreignId('target_classification_id')->constrained('security_classifications');
            $table->date('scheduled_date');
            $table->string('status', 50)->default('scheduled')->comment('scheduled, executed, cancelled');
            $table->foreignId('scheduled_by')->constrained('users');
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->timestamp('executed_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('scheduled_date', 'idx_declass_date');
            $table->index('status', 'idx_declass_status');
            $table->index('object_iri', 'idx_declass_iri');
        });

        // Security access log — compliance audit trail for all access grants/revocations
        Schema::create('security_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->comment('grant, revoke, request, approve, deny, classify, declassify');
            $table->string('target_type', 50)->comment('user, object, compartment');
            $table->string('target_id', 2048)->nullable();
            $table->foreignId('classification_id')->nullable()->constrained('security_classifications');
            $table->foreignId('compartment_id')->nullable()->constrained('security_compartments');
            $table->string('ip_address', 45)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action', 'idx_sec_log_action');
            $table->index('user_id', 'idx_sec_log_user');
            $table->index('created_at', 'idx_sec_log_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_access_log');
        Schema::dropIfExists('object_declassification_schedule');
        Schema::dropIfExists('object_access_grants');
        Schema::dropIfExists('security_access_requests');
        Schema::dropIfExists('object_compartment_access');
        Schema::dropIfExists('user_compartment_access');
        Schema::dropIfExists('security_compartments');
    }
};
