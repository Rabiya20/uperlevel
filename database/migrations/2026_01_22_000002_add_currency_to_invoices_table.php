<?php

use App\Models\FinanceSettings;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency', 3)->nullable()->after('due_date');
        });

        // Every invoice issued so far was implicitly billed in its tenant's
        // base currency (there was no currency field to say otherwise) —
        // backfill from finance_settings rather than leaving them blank.
        Invoice::whereNull('currency')->each(function (Invoice $invoice) {
            $code = FinanceSettings::where('tenant_id', $invoice->tenant_id)->value('currency_code') ?? 'USD';
            $invoice->update(['currency' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
