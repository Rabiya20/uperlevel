<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

            // Ordered [{key, label, is_won, is_lost}] — the pipeline shown
            // on every lead's status changer.
            $table->json('pipeline_stages')->nullable();

            // Free-text source labels offered on the lead form, e.g.
            // ["Website", "Fiverr", "Upwork"].
            $table->json('lead_sources')->nullable();

            // manual | round_robin | rule_based — only "manual" actually
            // assigns anything right now; the other two are stored for a
            // future auto-assignment engine.
            $table->string('assignment_mode', 20)->default('manual');
            $table->string('rule_based_field', 20)->nullable();

            $table->boolean('approval_required')->default(true);

            // skip | allow — "merge" isn't offered until bulk import
            // (where duplicate merging actually matters) exists.
            $table->string('duplicate_handling', 10)->default('skip');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_settings');
    }
};
