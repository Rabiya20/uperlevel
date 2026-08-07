<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

            // Currency
            $table->string('currency_code', 6)->default('USD');
            $table->string('currency_symbol', 6)->default('$');

            // Tax
            $table->string('tax_label', 30)->default('Sales Tax');
            $table->decimal('tax_percentage', 5, 2)->default(0);

            // Invoice numbering — e.g. prefix "INV-", padding 4 => INV-0001
            $table->string('invoice_prefix', 20)->default('INV-');
            $table->unsignedInteger('invoice_next_number')->default(1);
            $table->unsignedTinyInteger('invoice_number_padding')->default(4);

            // Payment terms
            $table->unsignedSmallInteger('default_payment_terms_days')->default(15);
            $table->decimal('late_fee_percentage', 5, 2)->nullable();

            // Fiscal year — month the fiscal year starts (1 = January)
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);

            // Free-form company banking details, shown on invoices
            $table->text('bank_details')->nullable();

            // Default terms/notes printed on every invoice footer
            $table->text('invoice_notes_default')->nullable();

            // Which payment methods this company accepts, e.g. ["bank_transfer","card","cash"]
            $table->json('accepted_payment_methods')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
    }
};