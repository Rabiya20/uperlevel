<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('overtime_enabled')->default(false);
            // Informational for now — no payroll module exists yet to apply it to.
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5);
            // Minutes past shift-end before extra time counts as overtime.
            $table->unsignedSmallInteger('overtime_threshold_minutes')->default(15);
            // Carbon dayOfWeek ints (0=Sunday..6=Saturday) — tells a real
            // absence apart from a weekend when computing monthly stats.
            $table->json('working_days')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
    }
};
