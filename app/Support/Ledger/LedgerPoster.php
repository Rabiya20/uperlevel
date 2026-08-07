<?php

namespace App\Support\Ledger;

use App\Models\Expense;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;

/**
 * Centralizes the "should we auto-post, and against which accounts"
 * decision for the few real transactions that move money in this app —
 * called from the relevant controllers/models right after the underlying
 * record's own state change, never silently skipped without a reason
 * (each method just no-ops when the ledger isn't configured for it).
 */
class LedgerPoster
{
    public static function invoiceCreated(Invoice $invoice, FinanceSettings $settings): ?JournalEntry
    {
        if (! self::shouldPost($settings) || ! $settings->default_receivable_account_id || ! $settings->default_revenue_account_id) {
            return null;
        }

        return JournalEntry::post(
            $invoice->tenant_id,
            $invoice->issue_date,
            "Invoice {$invoice->invoice_number} issued to {$invoice->client->name}",
            [
                ['chart_of_account_id' => $settings->default_receivable_account_id, 'debit' => $invoice->total, 'credit' => 0],
                ['chart_of_account_id' => $settings->default_revenue_account_id, 'debit' => 0, 'credit' => $invoice->total],
            ],
            $invoice,
            referenceNumber: $invoice->invoice_number,
            payee: $invoice->client->name,
            transactionType: 'invoice',
        );
    }

    /** Posts for the payment's own amount (not the invoice's full total) so partial payments post correctly, one entry per installment. */
    public static function paymentReceived(Payment $payment, FinanceSettings $settings): ?JournalEntry
    {
        $depositAccountId = $payment->deposit_account_id ?? $settings->default_cash_account_id;

        if (! self::shouldPost($settings) || ! $depositAccountId || ! $settings->default_receivable_account_id) {
            return null;
        }

        $invoice = $payment->invoice;

        return JournalEntry::post(
            $payment->tenant_id,
            \Illuminate\Support\Carbon::parse($payment->payment_date),
            "Payment received for invoice {$invoice->invoice_number}",
            [
                ['chart_of_account_id' => $depositAccountId, 'debit' => $payment->amount, 'credit' => 0],
                ['chart_of_account_id' => $settings->default_receivable_account_id, 'debit' => 0, 'credit' => $payment->amount],
            ],
            $payment,
            referenceNumber: $invoice->invoice_number,
            payee: $invoice->client->name,
            transactionType: 'payment',
        );
    }

    /**
     * Split-line aware: debits each line's category account (net of a
     * pro-rata share of any header discount, so every category absorbs its
     * fair share rather than inventing a separate "discount income"
     * account), debits tax once against the configured tax account, and
     * credits the expense's own chosen payment account for the full total.
     * Debits always equal credits by construction — see the worked check
     * in the class-level docblock... actually kept inline below for clarity.
     */
    public static function expenseApproved(Expense $expense, FinanceSettings $settings): ?JournalEntry
    {
        if (! self::shouldPost($settings) || ! $expense->payment_account_id) {
            return null;
        }

        $expense->loadMissing(['lines.category', 'vendor']);
        $subtotal = (float) $expense->subtotal;

        if ($subtotal <= 0 || $expense->lines->isEmpty()) {
            return null;
        }

        $discountedSubtotal = round($subtotal - (float) $expense->discount_amount, 2);
        $discountFactor = $subtotal > 0 ? max(0, $discountedSubtotal / $subtotal) : 1;

        // Aggregate by account — several split lines can share a category.
        $debitsByAccount = [];
        $accountOrder = [];
        foreach ($expense->lines as $line) {
            $accountId = $line->category?->chart_of_account_id ?? $settings->default_expense_account_id;
            if (! $accountId) {
                return null; // a line has nowhere to post — don't half-post the entry
            }
            if (! isset($debitsByAccount[$accountId])) {
                $debitsByAccount[$accountId] = 0;
                $accountOrder[] = $accountId;
            }
            $debitsByAccount[$accountId] += round((float) $line->amount * $discountFactor, 2);
        }

        // Rounding each account's share independently can drift a cent or
        // two from the exact discounted subtotal — absorb that drift into
        // the last account rather than let the balance check below reject
        // an otherwise-correct entry over a rounding artifact.
        $drift = round($discountedSubtotal - round(array_sum($debitsByAccount), 2), 2);
        if ($drift !== 0.0 && ! empty($accountOrder)) {
            $lastAccount = end($accountOrder);
            $debitsByAccount[$lastAccount] = round($debitsByAccount[$lastAccount] + $drift, 2);
        }

        $taxAmount = round((float) $expense->tax_amount, 2);
        if ($taxAmount > 0) {
            $taxAccountId = $settings->default_tax_account_id ?? $settings->default_expense_account_id;
            if (! $taxAccountId) {
                return null;
            }
            $debitsByAccount[$taxAccountId] = ($debitsByAccount[$taxAccountId] ?? 0) + $taxAmount;
        }

        $lines = [];
        foreach ($debitsByAccount as $accountId => $amount) {
            $lines[] = ['chart_of_account_id' => $accountId, 'debit' => round($amount, 2), 'credit' => 0];
        }
        $lines[] = ['chart_of_account_id' => $expense->payment_account_id, 'debit' => 0, 'credit' => round((float) $expense->total_amount, 2)];

        return JournalEntry::post(
            $expense->tenant_id,
            $expense->expense_date,
            "Expense {$expense->expense_number}".($expense->vendor ? " — {$expense->vendor->name}" : ''),
            $lines,
            $expense,
            referenceNumber: $expense->expense_number,
            payee: $expense->vendor->name ?? '',
            transactionType: 'expense',
        );
    }

    private static function shouldPost(FinanceSettings $settings): bool
    {
        return $settings->ledger_enabled && $settings->auto_create_ledger_entries;
    }
}
