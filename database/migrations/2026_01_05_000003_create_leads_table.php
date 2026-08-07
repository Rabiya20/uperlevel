<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->string('country')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('description')->nullable();

            // A pipeline stage key from the tenant's crm_settings.
            $table->string('status', 40);
            // Saved when a lead gets locked for approval, so a reject can
            // restore the stage it was in before "Won" was applied.
            $table->string('previous_status', 40)->nullable();

            $table->foreignId('assigned_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->boolean('is_locked')->default(false);
            // null | pending_approval | approved | rejected
            $table->string('conversion_status', 20)->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
