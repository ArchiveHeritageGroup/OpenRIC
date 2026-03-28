<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rights_statements', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048);
            $table->string('rights_basis', 50); // copyright, license, statute, other
            $table->string('rights_holder_name', 512)->nullable();
            $table->string('rights_holder_iri', 2048)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('documentation_iri', 2048)->nullable();
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('rights_basis');
        });

        Schema::create('embargoes', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048);
            $table->text('reason')->nullable();
            $table->date('embargo_start');
            $table->date('embargo_end')->nullable();
            $table->string('status', 50)->default('active'); // active, lifted, expired
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('status');
            $table->index('embargo_end');
        });

        Schema::create('tk_labels', function (Blueprint $table) {
            $table->id();
            $table->string('entity_iri', 2048);
            $table->string('label_type', 100);
            $table->string('label_iri', 2048)->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entity_iri');
            $table->index('label_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tk_labels');
        Schema::dropIfExists('embargoes');
        Schema::dropIfExists('rights_statements');
    }
};
