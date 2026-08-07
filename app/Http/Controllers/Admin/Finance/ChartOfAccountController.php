<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    /**
     * Starter chart used by "Load default chart of accounts" — a minimal
     * set covering the five account types, seeded only for codes the
     * tenant doesn't already have.
     */
    private const DEFAULTS = [
        ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
        ['code' => '1010', 'name' => 'Bank', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
        ['code' => '2100', 'name' => 'Taxes Payable', 'type' => 'liability'],
        ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
        ['code' => '3900', 'name' => 'Retained Earnings', 'type' => 'equity'],
        ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income'],
        ['code' => '4900', 'name' => 'Other Income', 'type' => 'income'],
        ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
        ['code' => '5100', 'name' => 'Operating Expenses', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'Payroll Expenses', 'type' => 'expense'],
        ['code' => '5300', 'name' => 'Rent Expense', 'type' => 'expense'],
        ['code' => '5400', 'name' => 'Utilities Expense', 'type' => 'expense'],
    ];

    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        return view('admin.finance.coa.index', compact('accounts'));
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $parents = ChartOfAccount::where('tenant_id', $tenant->id)->orderBy('code')->get();

        return view('admin.finance.coa.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        ChartOfAccount::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.finance.coa.index')->with('status', 'Account added.');
    }

    public function edit(ChartOfAccount $coa): View
    {
        $account = $coa;
        $this->authorizeTenant($account);

        $parents = ChartOfAccount::where('tenant_id', $account->tenant_id)
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        return view('admin.finance.coa.edit', compact('account', 'parents'));
    }

    public function update(Request $request, ChartOfAccount $coa): RedirectResponse
    {
        $account = $coa;
        $this->authorizeTenant($account);

        $data = $this->validated($request, $account->tenant_id, $account->id);
        $account->update($data);

        return redirect()->route('admin.finance.coa.index')->with('status', 'Account updated.');
    }

    public function destroy(ChartOfAccount $coa): RedirectResponse
    {
        $account = $coa;
        $this->authorizeTenant($account);
        $account->delete();

        return back()->with('status', 'Account removed.');
    }

    public function seedDefaults(): RedirectResponse
    {
        $tenant = $this->tenant();
        $existingCodes = ChartOfAccount::where('tenant_id', $tenant->id)->pluck('code')->all();

        foreach (self::DEFAULTS as $default) {
            if (in_array($default['code'], $existingCodes, true)) {
                continue;
            }

            ChartOfAccount::create([...$default, 'tenant_id' => $tenant->id]);
        }

        return redirect()->route('admin.finance.coa.index')->with('status', 'Default chart of accounts loaded.');
    }

    private function tenant()
    {
        $tenant = App::make('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(ChartOfAccount $account): void
    {
        abort_unless($account->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('chart_of_accounts', 'code')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', Rule::in(ChartOfAccount::TYPES)],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('chart_of_accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        // Views always submit a hidden "0" fallback ahead of the checkbox.
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
