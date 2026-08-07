<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'currency_code',
        'currency_symbol',
        'decimal_precision',
        'currency_symbol_position',
        'enabled_currencies',
        'tax_label',
        'tax_percentage',
        'tax_inclusive_pricing',
        'invoice_prefix',
        'invoice_next_number',
        'invoice_number_padding',
        'invoice_reset_numbering_yearly',
        'invoice_last_reset_year',
        'invoice_default_discount_type',
        'invoice_default_discount_value',
        'invoice_allow_edit_after_sending',
        'invoice_overdue_grace_days',
        'default_payment_terms_days',
        'late_fee_percentage',
        'allow_partial_payments',
        'payment_reminder_days',
        'fiscal_year_start_month',
        'bank_accounts',
        'invoice_notes_default',
        'accepted_payment_methods',
        'payment_gateway_provider',
        'payment_gateway_mode',
        'payment_gateway_public_key',
        'payment_gateway_secret_key',
        'payment_gateway_webhook_secret',
        'expense_approval_required',
        'expense_max_amount',
        'ledger_enabled',
        'auto_create_ledger_entries',
        'default_cash_account_id',
        'default_receivable_account_id',
        'default_revenue_account_id',
        'default_payable_account_id',
        'default_expense_account_id',
        'default_tax_account_id',
        'expense_prefix',
        'expense_next_number',
        'expense_number_padding',
        'report_default_period',
        'report_include_tax',
        'report_export_format',
    ];

    protected $casts = [
        'accepted_payment_methods' => 'array',
        'enabled_currencies' => 'array',
        'bank_accounts' => 'array',
        'tax_percentage' => 'decimal:2',
        'tax_inclusive_pricing' => 'boolean',
        'late_fee_percentage' => 'decimal:2',
        'allow_partial_payments' => 'boolean',
        'payment_gateway_secret_key' => 'encrypted',
        'payment_gateway_webhook_secret' => 'encrypted',
        'invoice_reset_numbering_yearly' => 'boolean',
        'invoice_default_discount_value' => 'decimal:2',
        'invoice_allow_edit_after_sending' => 'boolean',
        'expense_approval_required' => 'boolean',
        'expense_max_amount' => 'decimal:2',
        'ledger_enabled' => 'boolean',
        'auto_create_ledger_entries' => 'boolean',
        'report_include_tax' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function defaultCashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_cash_account_id');
    }

    public function defaultReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_receivable_account_id');
    }

    public function defaultRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_revenue_account_id');
    }

    public function defaultPayableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_payable_account_id');
    }

    public function defaultExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_expense_account_id');
    }

    public function defaultTaxAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_tax_account_id');
    }

    public function auditLabel(): string
    {
        return 'Finance Settings';
    }

    /**
     * Every tenant gets exactly one settings row, created with sensible
     * defaults the first time this is called (e.g. when they first open
     * Finance > Setup).
     */
    public static function forTenant(Tenant $tenant): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['accepted_payment_methods' => ['bank_transfer', 'card', 'cash']]
        );
    }

    /**
     * Preview of the next invoice number that will be issued,
     * e.g. "INV-0001". Used on the Setup screen and later by the
     * Invoices module when an invoice is actually created.
     */
    public function nextInvoiceNumberPreview(): string
    {
        $number = str_pad((string) $this->invoice_next_number, $this->invoice_number_padding, '0', STR_PAD_LEFT);

        return $this->invoice_prefix.$number;
    }

    /**
     * Issues the previewed number and advances the counter — call once per
     * invoice actually created. Resets the counter to 1 first if yearly
     * reset is on and this is the first invoice issued in a new year.
     */
    public function consumeNextInvoiceNumber(): string
    {
        if ($this->invoice_reset_numbering_yearly && $this->invoice_last_reset_year !== now()->year) {
            $this->update(['invoice_next_number' => 1, 'invoice_last_reset_year' => now()->year]);
        }

        $number = $this->nextInvoiceNumberPreview();
        $this->increment('invoice_next_number');

        return $number;
    }

    public function nextExpenseNumberPreview(): string
    {
        $number = str_pad((string) $this->expense_next_number, $this->expense_number_padding, '0', STR_PAD_LEFT);

        return $this->expense_prefix.$number;
    }

    public function consumeNextExpenseNumber(): string
    {
        $number = $this->nextExpenseNumberPreview();
        $this->increment('expense_next_number');

        return $number;
    }

    /**
     * Symbol for a given ISO code, falling back to the code itself if it
     * isn't in config/currencies.php (defaults to the tenant's base currency).
     */
    public function currencySymbol(?string $code = null): string
    {
        $code = $code ?? $this->currency_code;

        return config("currencies.{$code}.symbol", $code);
    }

    /**
     * Base currency plus every additional currency this company has
     * enabled — used to populate currency dropdowns across Finance.
     */
    public function allCurrencies(): array
    {
        return array_values(array_unique(array_merge([$this->currency_code], $this->enabled_currencies ?? [])));
    }

    public function formatMoney(float|int|string $amount, ?string $currencyCode = null): string
    {
        $formatted = number_format((float) $amount, $this->decimal_precision ?? 2);
        $symbol = $this->currencySymbol($currencyCode);

        return $this->currency_symbol_position === 'after' ? $formatted.$symbol : $symbol.$formatted;
    }
}
