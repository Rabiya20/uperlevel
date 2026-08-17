<?php

use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Expense categories could previously be saved with no linked Chart of
     * Accounts entry (the form's "Posts to account" field was optional and
     * defaulted to blank) — meaning the category never appeared in COA and
     * never had anywhere of its own to post a ledger entry. Going forward,
     * ExpenseCategoryController auto-creates this link on save; this
     * one-time backfill does the same for every category that predates
     * that change, so nothing existing is left stranded.
     */
    public function up(): void
    {
        ExpenseCategory::whereNull('chart_of_account_id')->get()->each(function (ExpenseCategory $category) {
            $nextCode = ChartOfAccount::where('tenant_id', $category->tenant_id)
                ->pluck('code')
                ->map(fn ($code) => (int) $code)
                ->filter(fn ($code) => $code >= 5000 && $code < 6000)
                ->max();

            $account = ChartOfAccount::create([
                'tenant_id' => $category->tenant_id,
                'code' => (string) (($nextCode ?? 4990) + 10),
                'name' => $category->name,
                'type' => 'expense',
                'opening_balance' => 0,
                'is_active' => true,
            ]);

            $category->update(['chart_of_account_id' => $account->id]);
        });
    }

    public function down(): void
    {
        // Not reversible — by the time this would roll back, the accounts
        // created here may already have real journal entries posted against them.
    }
};
