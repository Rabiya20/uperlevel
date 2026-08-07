<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            // General
            $table->unsignedTinyInteger('decimal_precision')->default(2)->after('currency_symbol');
            $table->string('currency_symbol_position', 10)->default('before')->after('decimal_precision');

            // Invoices
            $table->boolean('invoice_reset_numbering_yearly')->default(false)->after('invoice_number_padding');
            $table->unsignedSmallInteger('invoice_last_reset_year')->nullable()->after('invoice_reset_numbering_yearly');
            $table->string('invoice_default_discount_type', 10)->nullable()->after('invoice_reset_numbering_yearly');
            $table->decimal('invoice_default_discount_value', 8, 2)->nullable()->after('invoice_default_discount_type');
            $table->boolean('invoice_allow_edit_after_sending')->default(false)->after('invoice_default_discount_value');
            $table->unsignedSmallInteger('invoice_overdue_grace_days')->default(0)->after('invoice_allow_edit_after_sending');

            // Tax
            $table->boolean('tax_inclusive_pricing')->default(false)->after('tax_percentage');

            // Expenses (tenant-wide defaults; a category can still be routed to a specific account)
            $table->boolean('expense_approval_required')->default(false)->after('payment_reminder_days');
            $table->decimal('expense_max_amount', 12, 2)->nullable()->after('expense_approval_required');

            // Ledger
            $table->boolean('ledger_enabled')->default(false)->after('expense_max_amount');
            $table->boolean('auto_create_ledger_entries')->default(false)->after('ledger_enabled');
            $table->foreignId('default_cash_account_id')->nullable()->after('auto_create_ledger_entries')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('default_receivable_account_id')->nullable()->after('default_cash_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('default_revenue_account_id')->nullable()->after('default_receivable_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('default_payable_account_id')->nullable()->after('default_revenue_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('default_expense_account_id')->nullable()->after('default_payable_account_id')->constrained('chart_of_accounts')->nullOnDelete();

            // Reporting preferences (stored now; Finance Reports screen is a future build)
            $table->string('report_default_period', 10)->default('monthly')->after('default_payable_account_id');
            $table->boolean('report_include_tax')->default(true)->after('report_default_period');
            $table->string('report_export_format', 10)->default('pdf')->after('report_include_tax');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('overdue_notified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('overdue_notified_at');
        });

        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_cash_account_id');
            $table->dropConstrainedForeignId('default_receivable_account_id');
            $table->dropConstrainedForeignId('default_revenue_account_id');
            $table->dropConstrainedForeignId('default_payable_account_id');
            $table->dropConstrainedForeignId('default_expense_account_id');

            $table->dropColumn([
                'decimal_precision',
                'currency_symbol_position',
                'invoice_reset_numbering_yearly',
                'invoice_last_reset_year',
                'invoice_default_discount_type',
                'invoice_default_discount_value',
                'invoice_allow_edit_after_sending',
                'invoice_overdue_grace_days',
                'tax_inclusive_pricing',
                'expense_approval_required',
                'expense_max_amount',
                'ledger_enabled',
                'auto_create_ledger_entries',
                'report_default_period',
                'report_include_tax',
                'report_export_format',
            ]);
        });
    }
};
