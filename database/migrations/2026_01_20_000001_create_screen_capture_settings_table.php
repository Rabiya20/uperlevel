<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_capture_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('enabled')->default(false);
            $table->string('interval_mode')->default('fixed'); // 'fixed' | 'random'
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->unsignedInteger('random_min_minutes')->nullable();
            $table->unsignedInteger('random_max_minutes')->nullable();
            $table->unsignedInteger('retention_days')->nullable()->default(30);
            $table->boolean('notify_employees')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_capture_settings');
    }
};
