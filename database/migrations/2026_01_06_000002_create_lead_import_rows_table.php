<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('lead_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');

            // Flat columns mirroring the sample template — kept as their
            // own columns (not JSON) so a rejected/pending batch can still
            // be queried/filtered before anything becomes a real Lead.
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->string('country')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('description')->nullable();

            // valid | duplicate | error
            $table->string('status', 10);
            $table->string('error_message')->nullable();
            $table->foreignId('duplicate_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('imported_lead_id')->nullable()->constrained('leads')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_import_rows');
    }
};
