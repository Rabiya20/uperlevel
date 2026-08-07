<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expense becomes a header + line-items document (like Invoice already is)
 * instead of a single category/amount row — no real expense data exists
 * yet in this tenant (confirmed at the end of the prior pass), so this
 * restructures the table directly rather than migrating old rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn(['expense_category_id', 'amount']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_number')->nullable()->after('tenant_id');
            $table->foreignId('vendor_id')->nullable()->after('expense_number')->constrained()->nullOnDelete();
            $table->string('reference_number')->nullable()->after('vendor_id');

            $table->foreignId('payment_account_id')->nullable()->after('project_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('payment_method')->nullable()->after('payment_account_id'); // cash, bank_transfer, cheque, card, online
            $table->string('payment_status')->default('unpaid')->after('payment_method'); // paid, unpaid, partial

            $table->decimal('subtotal', 12, 2)->default(0)->after('payment_status');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('total_amount', 12, 2)->default(0)->after('discount_amount');

            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->text('edit_reason')->nullable()->after('rejection_reason');

            $table->foreignId('deleted_by')->nullable()->after('edit_reason')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['tenant_id', 'expense_number']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('payment_account_id');
            $table->dropConstrainedForeignId('vendor_id');

            $table->dropColumn([
                'expense_number', 'reference_number', 'payment_method', 'payment_status',
                'subtotal', 'tax_amount', 'discount_amount', 'total_amount', 'edit_reason',
            ]);

            $table->foreignId('expense_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2)->nullable();
        });
    }
};
