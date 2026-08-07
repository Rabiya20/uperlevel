@extends('layouts.admin')

@section('title', 'Finance Setup — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Accounts & Finance — Setup</h2>
        <p>These settings apply to every invoice, expense, ledger entry and report generated for this company.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.finance.settings.update') }}" id="financeForm">
    @csrf
    @method('PUT')

    <div class="setup-shell">
        <div class="setup-nav" id="setupNav">
            @foreach ([
                'general' => 'General',
                'currency' => 'Currency',
                'invoices' => 'Invoices',
                'payments' => 'Payments',
                'expenses' => 'Expenses',
                'ledger' => 'Ledger',
                'tax' => 'Tax',
                'reporting' => 'Reporting',
            ] as $key => $label)
                <button type="button" class="setup-nav-btn{{ $loop->first ? ' active' : '' }}" data-category="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="setup-panels">

            {{-- General --}}
            <div class="setup-cat" data-category="general">
                <div class="panel">
                    <div class="panel-head"><h3>General</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Base currency</label>
                            <select class="f-input" name="currency_code" id="baseCurrency" required>
                                @foreach ($currencies as $code => $c)
                                    <option value="{{ $code }}" @selected(old('currency_code', $settings->currency_code) === $code)>{{ $code }} — {{ $c['name'] }} ({{ $c['symbol'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Currency symbol</label>
                            <input class="f-input" type="text" id="baseCurrencySymbol" value="{{ old('currency_symbol', $settings->currency_symbol) }}" readonly>
                            <p class="f-hint">Set automatically from the base currency.</p>
                        </div>
                        <div>
                            <label class="f-label">Symbol placement</label>
                            <select class="f-input" name="currency_symbol_position">
                                <option value="before" @selected(old('currency_symbol_position', $settings->currency_symbol_position) === 'before')>Before amount — {{ $settings->currencySymbol() }}100</option>
                                <option value="after" @selected(old('currency_symbol_position', $settings->currency_symbol_position) === 'after')>After amount — 100{{ $settings->currencySymbol() }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Decimal precision</label>
                            <input class="f-input" type="number" min="0" max="4" name="decimal_precision" value="{{ old('decimal_precision', $settings->decimal_precision) }}" required>
                            <p class="f-hint">Digits shown after the decimal point on amounts — e.g. 2 → 100.00</p>
                        </div>
                        <div>
                            <label class="f-label">Financial year start month</label>
                            <select class="f-input" name="fiscal_year_start_month" required>
                                @foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                    <option value="{{ $i + 1 }}" @selected(old('fiscal_year_start_month', $settings->fiscal_year_start_month) == $i + 1)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Timezone</label>
                            <div class="f-input" style="background:var(--bg);color:var(--ink-soft);">{{ $currentTenant->timezone ?? 'UTC' }}</div>
                            <p class="f-hint">Set company-wide under <a href="{{ route('admin.company.settings') }}">Company → Settings</a>.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Currency --}}
            <div class="setup-cat" data-category="currency" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Multi-Currency</h3></div>
                    <div style="padding:16px 20px;">
                        <label class="f-label">Accepted currencies</label>
                        <p class="f-hint" style="margin:0 0 10px;">Other currencies this company can invoice or report in, besides the base currency above. Exchange rates are entered per document for now — automatic rate lookups aren't wired up yet.</p>
                        <div id="currencyRows" style="display:flex;flex-direction:column;gap:8px;"></div>
                        <button type="button" class="btn btn-ghost" id="addCurrencyRow" style="margin-top:10px;padding:7px 12px;font-size:12.5px;">+ Add currency</button>
                    </div>
                </div>
            </div>

            {{-- Invoices --}}
            <div class="setup-cat" data-category="invoices" style="display:none;">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Invoice Numbering</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Prefix</label>
                            <input class="f-input" type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings->invoice_prefix) }}" placeholder="INV-" maxlength="20" required>
                        </div>
                        <div>
                            <label class="f-label">Next number</label>
                            <input class="f-input" type="number" min="1" name="invoice_next_number" value="{{ old('invoice_next_number', $settings->invoice_next_number) }}" required>
                        </div>
                        <div>
                            <label class="f-label">Number padding (digits)</label>
                            <input class="f-input" type="number" min="1" max="8" name="invoice_number_padding" value="{{ old('invoice_number_padding', $settings->invoice_number_padding) }}" required>
                        </div>
                        <div>
                            <label class="f-label">Preview</label>
                            <div class="f-preview">{{ $settings->nextInvoiceNumberPreview() }}</div>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="invoice_reset_numbering_yearly" value="0">
                                <input type="checkbox" name="invoice_reset_numbering_yearly" value="1" @checked(old('invoice_reset_numbering_yearly', $settings->invoice_reset_numbering_yearly))>
                                Reset numbering back to 1 every calendar year
                            </label>
                        </div>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Invoice Defaults & Behavior</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Default due days</label>
                            <input class="f-input" type="number" min="0" max="365" name="default_payment_terms_days" value="{{ old('default_payment_terms_days', $settings->default_payment_terms_days) }}" required>
                            <p class="f-hint">e.g. 15 = Net 15</p>
                        </div>
                        <div>
                            <label class="f-label">Auto mark overdue after (days past due)</label>
                            <input class="f-input" type="number" min="0" max="90" name="invoice_overdue_grace_days" value="{{ old('invoice_overdue_grace_days', $settings->invoice_overdue_grace_days) }}" required>
                        </div>
                        <div>
                            <label class="f-label">Default discount type — optional</label>
                            <select class="f-input" name="invoice_default_discount_type">
                                <option value="">None</option>
                                <option value="percentage" @selected(old('invoice_default_discount_type', $settings->invoice_default_discount_type) === 'percentage')>Percentage (%)</option>
                                <option value="fixed" @selected(old('invoice_default_discount_type', $settings->invoice_default_discount_type) === 'fixed')>Fixed amount</option>
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Default discount value</label>
                            <input class="f-input" type="number" step="0.01" min="0" name="invoice_default_discount_value" value="{{ old('invoice_default_discount_value', $settings->invoice_default_discount_value) }}">
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="invoice_allow_edit_after_sending" value="0">
                                <input type="checkbox" name="invoice_allow_edit_after_sending" value="1" @checked(old('invoice_allow_edit_after_sending', $settings->invoice_allow_edit_after_sending))>
                                Allow editing an invoice after it's been sent
                            </label>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3>Default Invoice Footer / Terms</h3></div>
                    <div style="padding:16px 20px;">
                        <textarea class="f-input" name="invoice_notes_default" rows="4" placeholder="e.g. Thank you for your business. Payment due within terms above.">{{ old('invoice_notes_default', $settings->invoice_notes_default) }}</textarea>
                        <p class="f-hint">Pre-filled on new invoices — editable per invoice.</p>
                    </div>
                </div>
            </div>

            {{-- Payments --}}
            <div class="setup-cat" data-category="payments" style="display:none;">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Accepted Payment Methods</h3></div>
                    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                        @php
                            $methods = ['bank_transfer' => 'Bank Transfer', 'card' => 'Card', 'cash' => 'Cash', 'online_gateway' => 'Online Gateway'];
                            $selected = old('accepted_payment_methods', $settings->accepted_payment_methods ?? []);
                        @endphp
                        @foreach ($methods as $key => $label)
                            <label class="f-check">
                                <input type="checkbox" name="accepted_payment_methods[]" value="{{ $key }}" id="method-{{ $key }}" @checked(in_array($key, $selected))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="panel" id="gatewayPanel" style="display:none;margin-bottom:18px;">
                    <div class="panel-head"><h3>Payment Gateway Setup</h3></div>
                    <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Provider</label>
                            <select class="f-input" name="payment_gateway_provider">
                                <option value="">Select provider…</option>
                                @foreach (['stripe' => 'Stripe', 'paypal' => 'PayPal', 'razorpay' => 'Razorpay', 'other' => 'Other'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('payment_gateway_provider', $settings->payment_gateway_provider) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Mode</label>
                            <div style="display:flex;gap:16px;padding-top:9px;">
                                @foreach (['sandbox' => 'Sandbox', 'live' => 'Live'] as $key => $label)
                                    <label class="f-check">
                                        <input type="radio" name="payment_gateway_mode" value="{{ $key }}" @checked(old('payment_gateway_mode', $settings->payment_gateway_mode ?? 'sandbox') === $key)>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-label">Public / API key</label>
                            <input class="f-input" type="text" name="payment_gateway_public_key" value="{{ old('payment_gateway_public_key', $settings->payment_gateway_public_key) }}" autocomplete="off">
                        </div>
                        <div>
                            <label class="f-label">Secret key</label>
                            <input class="f-input" type="password" name="payment_gateway_secret_key" value="{{ old('payment_gateway_secret_key') }}" autocomplete="new-password" placeholder="{{ $settings->payment_gateway_secret_key ? '•••••••• saved — leave blank to keep' : '' }}">
                        </div>
                        <div>
                            <label class="f-label">Webhook secret — optional</label>
                            <input class="f-input" type="password" name="payment_gateway_webhook_secret" value="{{ old('payment_gateway_webhook_secret') }}" autocomplete="new-password" placeholder="{{ $settings->payment_gateway_webhook_secret ? '•••••••• saved — leave blank to keep' : '' }}">
                        </div>
                        <p class="f-hint" style="grid-column:1 / -1;margin:0;">Stored securely and never shown on invoices — only used to process online payments.</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Payment Terms</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Late fee (%) — optional</label>
                            <input class="f-input" type="number" step="0.01" min="0" max="100" name="late_fee_percentage" value="{{ old('late_fee_percentage', $settings->late_fee_percentage) }}">
                        </div>
                        <div>
                            <label class="f-label">Payment reminder (days before due)</label>
                            <input class="f-input" type="number" min="0" max="90" name="payment_reminder_days" value="{{ old('payment_reminder_days', $settings->payment_reminder_days) }}" placeholder="e.g. 3">
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="allow_partial_payments" value="0">
                                <input type="checkbox" name="allow_partial_payments" value="1" @checked(old('allow_partial_payments', $settings->allow_partial_payments))>
                                Allow clients to pay invoices in partial installments
                            </label>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3>Bank Accounts</h3></div>
                    <div style="padding:16px 20px;">
                        <div id="bankRows" style="display:flex;flex-direction:column;gap:12px;"></div>
                        <button type="button" class="btn btn-ghost" id="addBankRow" style="margin-top:12px;padding:7px 12px;font-size:12.5px;">+ Add bank account</button>
                        <p class="f-hint">Shown on every invoice so clients know where to send payment. Mark one account "Primary" — it's used as the default.</p>
                    </div>
                </div>
            </div>

            {{-- Expenses --}}
            <div class="setup-cat" data-category="expenses" style="display:none;">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Expense Numbering</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Prefix</label>
                            <input class="f-input" type="text" name="expense_prefix" value="{{ old('expense_prefix', $settings->expense_prefix) }}" placeholder="EXP-" maxlength="20" required>
                        </div>
                        <div>
                            <label class="f-label">Next number</label>
                            <input class="f-input" type="number" min="1" name="expense_next_number" value="{{ old('expense_next_number', $settings->expense_next_number) }}" required>
                        </div>
                        <div>
                            <label class="f-label">Number padding (digits)</label>
                            <input class="f-input" type="number" min="1" max="8" name="expense_number_padding" value="{{ old('expense_number_padding', $settings->expense_number_padding) }}" required>
                        </div>
                        <div>
                            <label class="f-label">Preview</label>
                            <div class="f-preview">{{ $settings->nextExpenseNumberPreview() }}</div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" style="justify-content:space-between;">
                        <h3>Expense Rules</h3>
                        <a href="{{ route('admin.finance.expense-categories.index') }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Manage Categories →</a>
                    </div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="expense_approval_required" value="0">
                                <input type="checkbox" name="expense_approval_required" value="1" @checked(old('expense_approval_required', $settings->expense_approval_required))>
                                New expenses require approval before they're recorded
                            </label>
                        </div>
                        <div>
                            <label class="f-label">Max expense amount — optional</label>
                            <input class="f-input" type="number" step="0.01" min="0" name="expense_max_amount" value="{{ old('expense_max_amount', $settings->expense_max_amount) }}" placeholder="No limit">
                            <p class="f-hint">A single expense above this amount is rejected at entry.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ledger --}}
            <div class="setup-cat" data-category="ledger" style="display:none;">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Ledger & Accounting Rules</h3></div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:12px;">
                        <label class="f-check">
                            <input type="hidden" name="ledger_enabled" value="0">
                            <input type="checkbox" name="ledger_enabled" value="1" id="ledgerEnabled" @checked(old('ledger_enabled', $settings->ledger_enabled))>
                            Enable the general ledger
                        </label>
                        <label class="f-check">
                            <input type="hidden" name="auto_create_ledger_entries" value="0">
                            <input type="checkbox" name="auto_create_ledger_entries" value="1" @checked(old('auto_create_ledger_entries', $settings->auto_create_ledger_entries))>
                            Automatically post journal entries for invoices and approved expenses
                        </label>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Default Accounts</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        @php
                            $accountOptions = fn ($type) => $accounts->where('type', $type);
                        @endphp
                        <div>
                            <label class="f-label">Cash / Bank</label>
                            <select class="f-input" name="default_cash_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('asset') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_cash_account_id', $settings->default_cash_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Accounts Receivable</label>
                            <select class="f-input" name="default_receivable_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('asset') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_receivable_account_id', $settings->default_receivable_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Revenue</label>
                            <select class="f-input" name="default_revenue_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('income') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_revenue_account_id', $settings->default_revenue_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Accounts Payable — optional</label>
                            <select class="f-input" name="default_payable_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('liability') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_payable_account_id', $settings->default_payable_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Default Expense Account</label>
                            <select class="f-input" name="default_expense_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('expense') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_expense_account_id', $settings->default_expense_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Default Tax Account — optional</label>
                            <select class="f-input" name="default_tax_account_id">
                                <option value="">— Select —</option>
                                @foreach ($accountOptions('expense') as $a)
                                    <option value="{{ $a->id }}" @selected(old('default_tax_account_id', $settings->default_tax_account_id) == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="f-hint" style="grid-column:1 / -1;margin:0;">Used when auto-posting journal entries — an expense category with its own account overrides the default expense account, and tax on an expense posts to the tax account (falling back to the default expense account if not set).</p>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3>Chart of Accounts</h3></div>
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <p class="f-hint" style="margin:0;">{{ $coaCount }} {{ $coaCount === 1 ? 'account' : 'accounts' }} configured — assets, liabilities, equity, income & expense accounts used across ledger entries and reports.</p>
                        <a href="{{ route('admin.finance.coa.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                    </div>
                </div>
            </div>

            {{-- Tax --}}
            <div class="setup-cat" data-category="tax" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Tax Configuration</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Tax label</label>
                            <input class="f-input" type="text" name="tax_label" value="{{ old('tax_label', $settings->tax_label) }}" placeholder="Sales Tax / GST / VAT" maxlength="30" required>
                        </div>
                        <div>
                            <label class="f-label">Tax percentage (%)</label>
                            <input class="f-input" type="number" step="0.01" min="0" max="100" name="tax_percentage" value="{{ old('tax_percentage', $settings->tax_percentage) }}" required>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="tax_inclusive_pricing" value="0">
                                <input type="checkbox" name="tax_inclusive_pricing" value="1" @checked(old('tax_inclusive_pricing', $settings->tax_inclusive_pricing))>
                                Prices entered already include tax (tax-inclusive)
                            </label>
                            <p class="f-hint">Off = tax is added on top of the amount entered (tax-exclusive, the default).</p>
                        </div>
                        <p class="f-hint" style="grid-column:1 / -1;margin:0;">One tax rate applies tenant-wide for now — multiple simultaneous tax types (e.g. VAT + GST) aren't supported yet.</p>
                    </div>
                </div>
            </div>

            {{-- Reporting --}}
            <div class="setup-cat" data-category="reporting" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Reporting Preferences</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Default report period</label>
                            <select class="f-input" name="report_default_period">
                                <option value="monthly" @selected(old('report_default_period', $settings->report_default_period) === 'monthly')>Monthly</option>
                                <option value="quarterly" @selected(old('report_default_period', $settings->report_default_period) === 'quarterly')>Quarterly</option>
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Default export format</label>
                            <select class="f-input" name="report_export_format">
                                <option value="pdf" @selected(old('report_export_format', $settings->report_export_format) === 'pdf')>PDF</option>
                                <option value="excel" @selected(old('report_export_format', $settings->report_export_format) === 'excel')>Excel</option>
                            </select>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="f-check">
                                <input type="hidden" name="report_include_tax" value="0">
                                <input type="checkbox" name="report_include_tax" value="1" @checked(old('report_include_tax', $settings->report_include_tax))>
                                Include tax amounts in financial reports
                            </label>
                        </div>
                        <p class="f-hint" style="grid-column:1 / -1;margin:0;">Stored now for a future Finance Reports screen — not yet consumed anywhere.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="submit" class="btn btn-primary">Save Finance Settings</button>
    </div>
</form>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-input[readonly]{background:var(--primary-soft);color:var(--primary-dark);font-weight:700;}
    .f-preview{padding:9px 11px;border-radius:8px;background:var(--primary-soft);color:var(--primary-dark);font-weight:700;font-size:13.5px;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:8px 0 0;}
    .f-hint a{color:var(--primary-dark);}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
    .repeat-row{border:1px solid var(--line);border-radius:8px;padding:12px;display:grid;grid-template-columns:1fr 1fr;gap:10px;position:relative;background:var(--bg);}
    .repeat-row .remove-row{position:absolute;top:8px;right:8px;background:none;border:none;color:var(--ink-soft);cursor:pointer;font-size:16px;line-height:1;padding:2px 6px;}
    .repeat-row .remove-row:hover{color:#c0392b;}
    .currency-row{display:flex;align-items:center;gap:10px;border:1px solid var(--line);border-radius:8px;padding:9px 11px;background:var(--bg);}
    .currency-row select{flex:1;}
    .currency-row .row-symbol{width:34px;text-align:center;font-weight:700;color:var(--primary-dark);}

    .setup-shell{display:grid;grid-template-columns:190px 1fr;gap:20px;align-items:start;}
    .setup-nav{display:flex;flex-direction:column;gap:4px;position:sticky;top:16px;}
    .setup-nav-btn{text-align:left;padding:10px 14px;border-radius:8px;border:none;background:transparent;color:var(--ink-soft);font-size:13px;font-weight:600;cursor:pointer;}
    .setup-nav-btn:hover{background:var(--bg);}
    .setup-nav-btn.active{background:var(--primary-soft);color:var(--primary-dark);}
    @media (max-width: 800px){ .setup-shell{grid-template-columns:1fr;} .setup-nav{flex-direction:row;flex-wrap:wrap;position:static;} }
</style>

<script>
    const CURRENCIES = @json($currencies);
    const currencyOptionsHtml = Object.keys(CURRENCIES).map(code =>
        `<option value="${code}">${code} — ${CURRENCIES[code].name} (${CURRENCIES[code].symbol})</option>`
    ).join('');

    // --- Sidebar category switching ---
    // Named setupNavBtns/setupCatPanels (not navBtns) — the shared header
    // partial (partials/admin-subnav.blade.php) already declares a global
    // `const navBtns` on every admin page; reusing that name here throws a
    // SyntaxError that silently kills this entire script block.
    const setupNavBtns = document.querySelectorAll('.setup-nav-btn');
    const setupCatPanels = document.querySelectorAll('.setup-cat');
    setupNavBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setupNavBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            setupCatPanels.forEach(p => { p.style.display = p.dataset.category === btn.dataset.category ? 'block' : 'none'; });
        });
    });

    // --- Base currency symbol, kept in sync as the dropdown changes ---
    const baseCurrency = document.getElementById('baseCurrency');
    const baseCurrencySymbol = document.getElementById('baseCurrencySymbol');
    baseCurrency.addEventListener('change', () => {
        const c = CURRENCIES[baseCurrency.value];
        if (c) baseCurrencySymbol.value = c.symbol;
    });

    // --- Accepted currencies repeater ---
    const currencyRows = document.getElementById('currencyRows');
    let currencyRowIndex = 0;

    function addCurrencyRow(selected) {
        const i = currencyRowIndex++;
        const row = document.createElement('div');
        row.className = 'currency-row';
        row.innerHTML = `
            <select name="enabled_currencies[]" class="f-input">${currencyOptionsHtml}</select>
            <span class="row-symbol"></span>
            <button type="button" class="remove-row" title="Remove">✕</button>
        `;
        const select = row.querySelector('select');
        const symbol = row.querySelector('.row-symbol');
        const updateSymbol = () => { symbol.textContent = (CURRENCIES[select.value] || {}).symbol || ''; };
        select.addEventListener('change', updateSymbol);
        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        if (selected && CURRENCIES[selected]) select.value = selected;
        updateSymbol();
        currencyRows.appendChild(row);
    }

    document.getElementById('addCurrencyRow').addEventListener('click', () => addCurrencyRow());
    @foreach ((old('enabled_currencies', $settings->enabled_currencies) ?? []) as $code)
        addCurrencyRow(@json($code));
    @endforeach

    // --- Bank accounts repeater ---
    const bankRows = document.getElementById('bankRows');
    let bankRowIndex = 0;

    function addBankRow(data) {
        data = data || {};
        const i = bankRowIndex++;
        const row = document.createElement('div');
        row.className = 'repeat-row';
        row.innerHTML = `
            <button type="button" class="remove-row" title="Remove">✕</button>
            <div>
                <label class="f-label">Bank name</label>
                <input class="f-input" type="text" name="bank_accounts[${i}][bank_name]" value="${data.bank_name || ''}">
            </div>
            <div>
                <label class="f-label">Account title</label>
                <input class="f-input" type="text" name="bank_accounts[${i}][account_title]" value="${data.account_title || ''}">
            </div>
            <div>
                <label class="f-label">Account number</label>
                <input class="f-input" type="text" name="bank_accounts[${i}][account_number]" value="${data.account_number || ''}">
            </div>
            <div>
                <label class="f-label">IBAN</label>
                <input class="f-input" type="text" name="bank_accounts[${i}][iban]" value="${data.iban || ''}">
            </div>
            <div>
                <label class="f-label">Currency</label>
                <select class="f-input" name="bank_accounts[${i}][currency_code]">${currencyOptionsHtml}</select>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <label class="f-check">
                    <input type="radio" name="bank_primary_selector" ${data.is_primary ? 'checked' : ''}>
                    Primary account
                </label>
                <input type="hidden" name="bank_accounts[${i}][is_primary]" value="${data.is_primary ? '1' : '0'}">
            </div>
        `;
        const currencySelect = row.querySelector('select');
        if (data.currency_code && CURRENCIES[data.currency_code]) currencySelect.value = data.currency_code;

        const primaryRadio = row.querySelector('input[name="bank_primary_selector"]');
        const primaryHidden = row.querySelector(`input[name="bank_accounts[${i}][is_primary]"]`);
        primaryRadio.addEventListener('change', () => {
            document.querySelectorAll('input[name="bank_primary_selector"]').forEach(r => {
                r.closest('.repeat-row').querySelector('input[type=hidden][name$="[is_primary]"]').value = r.checked ? '1' : '0';
            });
        });
        primaryHidden.value = data.is_primary ? '1' : '0';

        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        bankRows.appendChild(row);
    }

    document.getElementById('addBankRow').addEventListener('click', () => addBankRow());
    @php $bankAccounts = old('bank_accounts', $settings->bank_accounts) ?? []; @endphp
    @foreach ($bankAccounts as $account)
        addBankRow(@json($account));
    @endforeach
    if (bankRowIndex === 0) addBankRow();

    // --- Online gateway panel toggle ---
    const onlineGatewayCheckbox = document.getElementById('method-online_gateway');
    const gatewayPanel = document.getElementById('gatewayPanel');
    function syncGatewayPanel() { gatewayPanel.style.display = onlineGatewayCheckbox.checked ? 'block' : 'none'; }
    onlineGatewayCheckbox.addEventListener('change', syncGatewayPanel);
    syncGatewayPanel();
</script>
@endsection
