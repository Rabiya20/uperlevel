<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\FinanceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $settings = FinanceSettings::forTenant($tenant);
        $currencies = config('currencies');
        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('type')->orderBy('code')->get();
        $coaCount = $accounts->count();

        return view('admin.finance.settings', compact('settings', 'currencies', 'accounts', 'coaCount'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $currencyCodes = array_keys(config('currencies'));

        // Strip blank rows before validating — an "in" rule without
        // "required" silently lets empty-string elements through instead
        // of rejecting them, so clean the input rather than rely on that.
        $request->merge([
            'enabled_currencies' => array_values(array_filter(
                $request->input('enabled_currencies', []),
                fn ($code) => filled($code)
            )),
        ]);

        $accountIds = ChartOfAccount::where('tenant_id', $tenant->id)->pluck('id');

        $data = $request->validate([
            'currency_code' => ['required', 'string', Rule::in($currencyCodes)],
            'enabled_currencies' => ['nullable', 'array'],
            'enabled_currencies.*' => ['string', Rule::in($currencyCodes)],
            'decimal_precision' => ['required', 'integer', 'min:0', 'max:4'],
            'currency_symbol_position' => ['required', 'string', 'in:before,after'],
            'tax_label' => ['required', 'string', 'max:30'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive_pricing' => ['nullable', 'boolean'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_next_number' => ['required', 'integer', 'min:1'],
            'invoice_number_padding' => ['required', 'integer', 'min:1', 'max:8'],
            'invoice_reset_numbering_yearly' => ['nullable', 'boolean'],
            'invoice_default_discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'invoice_default_discount_value' => ['nullable', 'numeric', 'min:0'],
            'invoice_allow_edit_after_sending' => ['nullable', 'boolean'],
            'invoice_overdue_grace_days' => ['required', 'integer', 'min:0', 'max:90'],
            'default_payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'late_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allow_partial_payments' => ['nullable', 'boolean'],
            'payment_reminder_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'fiscal_year_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'invoice_notes_default' => ['nullable', 'string', 'max:2000'],
            'accepted_payment_methods' => ['nullable', 'array'],
            'accepted_payment_methods.*' => ['string', 'in:bank_transfer,card,cash,online_gateway'],

            'expense_prefix' => ['required', 'string', 'max:20'],
            'expense_next_number' => ['required', 'integer', 'min:1'],
            'expense_number_padding' => ['required', 'integer', 'min:1', 'max:8'],
            'expense_approval_required' => ['nullable', 'boolean'],
            'expense_max_amount' => ['nullable', 'numeric', 'min:0'],

            'ledger_enabled' => ['nullable', 'boolean'],
            'auto_create_ledger_entries' => ['nullable', 'boolean'],
            'default_cash_account_id' => ['nullable', Rule::in($accountIds)],
            'default_receivable_account_id' => ['nullable', Rule::in($accountIds)],
            'default_revenue_account_id' => ['nullable', Rule::in($accountIds)],
            'default_payable_account_id' => ['nullable', Rule::in($accountIds)],
            'default_expense_account_id' => ['nullable', Rule::in($accountIds)],
            'default_tax_account_id' => ['nullable', Rule::in($accountIds)],

            'report_default_period' => ['required', 'string', 'in:monthly,quarterly'],
            'report_include_tax' => ['nullable', 'boolean'],
            'report_export_format' => ['required', 'string', 'in:pdf,excel'],

            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['nullable', 'string', 'max:120'],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:120'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.iban' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.currency_code' => ['nullable', 'string', Rule::in($currencyCodes)],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],

            'payment_gateway_provider' => ['nullable', 'string', 'in:stripe,paypal,razorpay,other'],
            'payment_gateway_mode' => ['nullable', 'string', 'in:sandbox,live'],
            'payment_gateway_public_key' => ['nullable', 'string', 'max:255'],
            'payment_gateway_secret_key' => ['nullable', 'string', 'max:255'],
            'payment_gateway_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        // The repeater can submit rows the user added then left blank —
        // drop anything without at least a bank name or account number.
        $data['bank_accounts'] = collect($data['bank_accounts'] ?? [])
            ->filter(fn ($row) => filled($row['bank_name'] ?? null) || filled($row['account_number'] ?? null))
            ->map(fn ($row) => [
                'bank_name' => $row['bank_name'] ?? '',
                'account_title' => $row['account_title'] ?? '',
                'account_number' => $row['account_number'] ?? '',
                'iban' => $row['iban'] ?? '',
                'currency_code' => $row['currency_code'] ?? $data['currency_code'],
                'is_primary' => (bool) ($row['is_primary'] ?? false),
            ])
            ->values()
            ->all();

        $data['enabled_currencies'] = array_values(array_unique($data['enabled_currencies'] ?? []));

        // Symbol always derives from the code — never trust a hand-typed value.
        $data['currency_symbol'] = config("currencies.{$data['currency_code']}.symbol", $data['currency_code']);

        $data['allow_partial_payments'] = (bool) ($data['allow_partial_payments'] ?? false);
        $data['tax_inclusive_pricing'] = (bool) ($data['tax_inclusive_pricing'] ?? false);
        $data['invoice_reset_numbering_yearly'] = (bool) ($data['invoice_reset_numbering_yearly'] ?? false);
        $data['invoice_allow_edit_after_sending'] = (bool) ($data['invoice_allow_edit_after_sending'] ?? false);
        $data['expense_approval_required'] = (bool) ($data['expense_approval_required'] ?? false);
        $data['ledger_enabled'] = (bool) ($data['ledger_enabled'] ?? false);
        $data['auto_create_ledger_entries'] = (bool) ($data['auto_create_ledger_entries'] ?? false);
        $data['report_include_tax'] = (bool) ($data['report_include_tax'] ?? false);

        // Secrets are never re-rendered into the form — only overwrite the
        // stored value when the admin actually typed a new one.
        if (blank($data['payment_gateway_secret_key'] ?? null)) {
            unset($data['payment_gateway_secret_key']);
        }
        if (blank($data['payment_gateway_webhook_secret'] ?? null)) {
            unset($data['payment_gateway_webhook_secret']);
        }

        $settings = FinanceSettings::forTenant($tenant);
        $settings->update($data);

        return back()->with('status', 'Finance settings saved.');
    }
}
