<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100);
            $table->string('key', 255);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key'], 'uq_settings_group_key');
            $table->index('group', 'idx_settings_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
