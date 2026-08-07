<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        ExpenseCategory::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.finance.expense-categories.index')->with('status', 'Category added.');
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

        $expenseCategory->update($data);

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
