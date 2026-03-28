<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ACL tables — adapted from Heratio ahg-acl package.
 *
 * OpenRiC uses a dual-layer permission model:
 *   1. Role-based (roles + permissions tables from migration 000002) — coarse-grained
 *   2. Object-level ACL (these tables) — fine-grained per-object access control
 *
 * Heratio reference: ahg-acl/src/Services/AclService.php (498 lines)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ACL groups — permission groups like "Staff", "Editors", "Researchers"
        Schema::create('acl_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('acl_groups')
                ->nullOnDelete();
            $table->index('parent_id', 'idx_acl_group_parent');
            $table->index('is_active', 'idx_acl_group_active');
        });

        // User-to-group membership
        Schema::create('acl_user_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('acl_group_id')->constrained('acl_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'acl_group_id'], 'uq_acl_user_group');
        });

        // Object-level ACL permissions — grant/deny per user or group on specific objects
        // Heratio pattern: action = create|read|update|delete|publish|execute
        // grant_deny = 1 (grant) or 0 (deny), deny takes precedence
        Schema::create('acl_object_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('acl_group_id')->nullable()->constrained('acl_groups')->cascadeOnDelete();
            $table->string('object_iri', 2048)->nullable()->comment('NULL = applies to entity type globally');
            $table->string('entity_type', 100)->nullable()->comment('record, agent, place, etc.');
            $table->string('action', 50);
            $table->boolean('grant_deny')->default(true)->comment('true=grant, false=deny');
            $table->boolean('conditional')->default(false);
            $table->jsonb('conditions')->nullable()->comment('Conditional rule definitions');
            $table->integer('priority')->default(0)->comment('Higher priority rules evaluated first');
            $table->timestamps();

            $table->index('user_id', 'idx_acl_perm_user');
            $table->index('acl_group_id', 'idx_acl_perm_group');
            $table->index('entity_type', 'idx_acl_perm_entity_type');
            $table->index('action', 'idx_acl_perm_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acl_object_permissions');
        Schema::dropIfExists('acl_user_group');
        Schema::dropIfExists('acl_groups');
    }
};
