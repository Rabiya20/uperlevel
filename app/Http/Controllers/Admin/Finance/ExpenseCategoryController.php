<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $categories = ExpenseCategory::where('tenant_id', $tenant->id)
            ->withCount(['lines as expenses_count'])
            ->with('chartOfAccount')
            ->orderBy('name')
            ->get();

        return view('admin.finance.expense-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.finance.expense-categories.create', [
            'category' => null,
            'accounts' => $this->expenseAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        DB::transaction(function () use ($data, $tenant) {
            // No existing account picked — give this category its own, so
            // it always has somewhere to post to and always shows up in
            // Chart of Accounts, rather than silently falling back to a
            // shared/invisible tenant-wide default.
            if (empty($data['chart_of_account_id'])) {
                $data['chart_of_account_id'] = $this->createLinkedAccount($tenant->id, $data['name'])->id;
            }

            ExpenseCategory::create([...$data, 'tenant_id' => $tenant->id]);
        });

        return redirect()->route('admin.finance.expense-categories.index')->with('status', 'Category added — a matching Chart of Accounts entry was created automatically.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        $this->authorizeTenant($expenseCategory);

        return view('admin.finance.expense-categories.edit', [
            'category' => $expenseCategory,
            'accounts' => $this->expenseAccounts(),
        ]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorizeTenant($expenseCategory);
        $data = $this->validated($request, $expenseCategory->tenant_id, $expenseCategory->id);

        DB::transaction(function () use ($data, $expenseCategory) {
            if (empty($data['chart_of_account_id'])) {
                // Covers both a category saved before this account-linking
                // existed, and an admin clearing the field back to blank.
                $data['chart_of_account_id'] = $this->createLinkedAccount($expenseCategory->tenant_id, $data['name'])->id;
            } elseif (
                $data['chart_of_account_id'] == $expenseCategory->chart_of_account_id
                && $data['name'] !== $expenseCategory->name
                && ! ExpenseCategory::where('chart_of_account_id', $data['chart_of_account_id'])->where('id', '!=', $expenseCategory->id)->exists()
            ) {
                // Keep the linked account's name matching the category's —
                // but only when this is the only category pointing at it,
                // so an intentionally-shared account is never renamed out
                // from under another category.
                ChartOfAccount::where('id', $data['chart_of_account_id'])->update(['name' => $data['name']]);
            }

            $expenseCategory->update($data);
        });

        return redirect()->route('admin.finance.expense-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorizeTenant($expenseCategory);

        if ($expenseCategory->lines()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has expenses recorded against it — deactivate it instead of deleting.',
            ]);
        }

        $expenseCategory->delete();

        return redirect()->route('admin.finance.expense-categories.index')->with('status', 'Category removed.');
    }

    private function expenseAccounts()
    {
        return ChartOfAccount::where('tenant_id', $this->tenant()->id)->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();
    }

    private function createLinkedAccount(int $tenantId, string $name): ChartOfAccount
    {
        return ChartOfAccount::create([
            'tenant_id' => $tenantId,
            'code' => $this->nextExpenseAccountCode($tenantId),
            'name' => $name,
            'type' => 'expense',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    /** Next free code in the expense block (5000-5999) — codes stay editable afterward if the admin wants something else. */
    private function nextExpenseAccountCode(int $tenantId): string
    {
        $max = ChartOfAccount::where('tenant_id', $tenantId)
            ->pluck('code')
            ->map(fn ($code) => (int) $code)
            ->filter(fn ($code) => $code >= 5000 && $code < 6000)
            ->max();

        return (string) (($max ?? 4990) + 10);
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(ExpenseCategory $category): void
    {
        abort_unless($category->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('expense_categories', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId)],
            'chart_of_account_id' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('tenant_id', $tenantId)->where('type', 'expense')],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
