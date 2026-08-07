<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->string('expense_prefix')->default('EXP-')->after('invoice_number_padding');
            $table->unsignedInteger('expense_next_number')->default(1)->after('expense_prefix');
            $table->unsignedTinyInteger('expense_number_padding')->default(4)->after('expense_next_number');
            $table->foreignId('default_tax_account_id')->nullable()->after('default_expense_account_id')->constrained('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_tax_account_id');
            $table->dropColumn(['expense_prefix', 'expense_next_number', 'expense_number_padding']);
        });
    }
};
