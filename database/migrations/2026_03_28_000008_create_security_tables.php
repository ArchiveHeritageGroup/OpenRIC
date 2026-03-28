<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->integer('level');
            $table->string('color', 7)->default('#6c757d');
            $table->boolean('active')->default(true);
            $table->boolean('requires_2fa')->default(false);
            $table->boolean('watermark_required')->default(false);
            $table->boolean('download_allowed')->default(true);
            $table->boolean('print_allowed')->default(true);
            $table->boolean('copy_allowed')->default(true);
            $table->timestamps();
            $table->index('level', 'idx_sec_class_level');
            $table->index('active', 'idx_sec_class_active');
        });

        Schema::create('user_security_clearance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('classification_id')->constrained('security_classifications');
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('user_security_clearance_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 50);
            $table->unsignedBigInteger('previous_classification_id')->nullable();
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id', 'idx_sec_clear_log_user');
            $table->index('created_at', 'idx_sec_clear_log_date');
        });

        Schema::create('object_security_classification', function (Blueprint $table) {
            $table->id();
            $table->string('object_iri', 2048);
            $table->foreignId('classification_id')->constrained('security_classifications');
            $table->foreignId('classified_by')->constrained('users');
            $table->timestamp('classified_at')->useCurrent();
            $table->text('reason')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('object_iri', 'idx_obj_sec_iri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_security_classification');
        Schema::dropIfExists('user_security_clearance_log');
        Schema::dropIfExists('user_security_clearance');
        Schema::dropIfExists('security_classifications');
    }
};
