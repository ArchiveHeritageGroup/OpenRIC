<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance existing tables to match Heratio depth.
 *
 * - users: add contact info, API key, 2FA, login tracking
 * - settings: add type, description, sensitivity flag, updated_by
 * - audit_log: add request tracking, status, metadata
 * - security_classifications: add compartment association text
 */
return new class extends Migration
{
    public function up(): void
    {
        // Enhance users table — from Heratio ahg-user-manage (774 lines)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('users', 'institution')) {
                $table->string('institution', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 255)->nullable()->after('institution');
            }
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 255)->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('users', 'api_key')) {
                $table->string('api_key', 64)->nullable()->unique()->after('bio');
            }
            if (!Schema::hasColumn('users', 'api_key_expires_at')) {
                $table->timestamp('api_key_expires_at')->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('api_key_expires_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('two_factor_confirmed_at');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0)->after('last_login_ip');
            }
            if (!Schema::hasColumn('users', 'failed_login_count')) {
                $table->integer('failed_login_count')->default(0)->after('login_count');
            }
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_count');
            }
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->jsonb('preferences')->nullable()->after('locked_until')
                    ->comment('User UI preferences: theme, sidebar state, default view, etc.');
            }
        });

        // Enhance settings table — from Heratio ahg-core AhgSettingsService (508 lines)
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'type')) {
                $table->string('type', 50)->default('string')->after('value')
                    ->comment('string, integer, boolean, json, text, email, url');
            }
            if (!Schema::hasColumn('settings', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
            if (!Schema::hasColumn('settings', 'is_sensitive')) {
                $table->boolean('is_sensitive')->default(false)->after('description')
                    ->comment('If true, value is masked in UI and audit logs');
            }
            if (!Schema::hasColumn('settings', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('is_sensitive')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('settings', 'validation_rules')) {
                $table->string('validation_rules', 500)->nullable()->after('updated_by')
                    ->comment('Laravel validation rules for this setting');
            }
        });

        // Enhance audit_log table — from Heratio ahg-audit-trail (489 lines)
        Schema::table('audit_log', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_log', 'entity_slug')) {
                $table->string('entity_slug', 255)->nullable()->after('entity_id');
            }
            if (!Schema::hasColumn('audit_log', 'request_method')) {
                $table->string('request_method', 10)->nullable()->after('action_name');
            }
            if (!Schema::hasColumn('audit_log', 'request_uri')) {
                $table->text('request_uri')->nullable()->after('request_method');
            }
            if (!Schema::hasColumn('audit_log', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('changed_fields');
            }
            if (!Schema::hasColumn('audit_log', 'status')) {
                $table->string('status', 50)->default('success')->after('description')
                    ->comment('success, failure, error');
            }
            if (!Schema::hasColumn('audit_log', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('audit_log', 'duration_ms')) {
                $table->integer('duration_ms')->nullable()->after('error_message')
                    ->comment('Request duration in milliseconds');
            }
        });

        // Enhance security_classifications — add handling instructions
        Schema::table('security_classifications', function (Blueprint $table) {
            if (!Schema::hasColumn('security_classifications', 'handling_instructions')) {
                $table->text('handling_instructions')->nullable()->after('copy_allowed')
                    ->comment('Mandatory handling procedures for this classification level');
            }
            if (!Schema::hasColumn('security_classifications', 'banner_text')) {
                $table->string('banner_text', 500)->nullable()->after('handling_instructions')
                    ->comment('Warning banner text displayed on classified objects');
            }
            if (!Schema::hasColumn('security_classifications', 'retention_years')) {
                $table->integer('retention_years')->nullable()->after('banner_text')
                    ->comment('Default retention period before declassification review');
            }
        });

        // Add RiC sync tables — from Heratio ahg-ric RicController (2145 lines)
        Schema::create('ric_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->string('entity_iri', 2048);
            $table->string('action', 50)->comment('create, update, delete');
            $table->string('status', 50)->default('pending')->comment('pending, processing, synced, failed');
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->jsonb('payload')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_ric_sync_status');
            $table->index('entity_type', 'idx_ric_sync_entity');
            $table->index('scheduled_at', 'idx_ric_sync_scheduled');
        });

        Schema::create('ric_sync_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->nullable()->constrained('ric_sync_queue')->nullOnDelete();
            $table->string('entity_type', 100);
            $table->string('entity_iri', 2048);
            $table->string('action', 50);
            $table->string('status', 50)->comment('success, failure');
            $table->integer('triples_affected')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('entity_type', 'idx_ric_log_entity');
            $table->index('status', 'idx_ric_log_status');
            $table->index('created_at', 'idx_ric_log_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ric_sync_log');
        Schema::dropIfExists('ric_sync_queue');

        Schema::table('security_classifications', function (Blueprint $table) {
            $table->dropColumn(['handling_instructions', 'banner_text', 'retention_years']);
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropColumn(['entity_slug', 'request_method', 'request_uri', 'metadata', 'status', 'error_message', 'duration_ms']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['type', 'description', 'is_sensitive', 'updated_by', 'validation_rules']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'institution', 'department', 'job_title', 'bio',
                'api_key', 'api_key_expires_at',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'last_login_at', 'last_login_ip', 'login_count', 'failed_login_count',
                'locked_until', 'preferences',
            ]);
        });
    }
};
