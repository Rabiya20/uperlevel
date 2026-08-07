@extends('layouts.admin')

@section('title', 'Finance Overview — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Finance Overview</h2>
        <p>Revenue, outstanding balances, cash position and recent activity across Invoices, Payments and Expenses.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.finance.invoices.create') }}" class="btn btn-ghost">+ Create Invoice</a>
        <a href="{{ route('admin.finance.expenses.create') }}" class="btn btn-primary">+ Record Expense</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total Revenue</div>
        <div class="kpi-value">{{ $settings->formatMoney($totalRevenue) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Outstanding</div>
        <div class="kpi-value">{{ $settings->formatMoney($outstanding) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Overdue</div>
        <div class="kpi-value">{{ $settings->formatMoney($overdueAmount) }}</div>
        <div class="kpi-delta">{{ $overdueCount }} invoice{{ $overdueCount === 1 ? '' : 's' }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Net — This Month</div>
        <div class="kpi-value">{{ $settings->formatMoney($netThisMonth) }}</div>
        <div class="kpi-delta">{{ $settings->formatMoney($revenueThisMonth) }} in · {{ $settings->formatMoney($expensesThisMonth) }} out</div>
    </div>
</div>

<div class="panel" style="margin-top:18px;">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>Cash & Bank</h3>
        <a href="{{ route('admin.finance.ledger.index') }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Full Ledger →</a>
    </div>
    @if ($cashAndBank->isEmpty())
        <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No cash/bank accounts set up yet.</div>
    @else
        <table>
            <tr><th>Code</th><th>Account</th><th>Balance</th></tr>
            @foreach ($cashAndBank as $account)
                <tr>
                    <td style="font-weight:700;">{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td>{{ $settings->formatMoney($account->currentBalance()) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-top:18px;">
    <div class="panel">
        <div class="panel-head" style="justify-content:space-between;">
            <h3>Recent Invoices</h3>
            <a href="{{ route('admin.finance.invoices.index') }}" class="btn btn-ghost" style="padding:6px 10px;font-size:11.5px;">All →</a>
        </div>
        @if ($recentInvoices->isEmpty())
            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">None yet.</div>
        @else
            <table>
                <tr><th>Number</th><th>Client</th><th>Total</th></tr>
                @foreach ($recentInvoices as $invoice)
                    <tr>
                        <td><a href="{{ route('admin.finance.invoices.show', $invoice) }}" style="color:var(--primary-dark);">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->client->name ?? '—' }}</td>
                        <td>{{ number_format((float) $invoice->total, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head" style="justify-content:space-between;">
            <h3>Recent Payments</h3>
            <a href="{{ route('admin.finance.payments.index') }}" class="btn btn-ghost" style="padding:6px 10px;font-size:11.5px;">All →</a>
        </div>
        @if ($recentPayments->isEmpty())
            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">None yet.</div>
        @else
            <table>
                <tr><th>Invoice</th><th>Client</th><th>Amount</th></tr>
                @foreach ($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->invoice->invoice_number ?? '—' }}</td>
                        <td>{{ $payment->client->name ?? '—' }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head" style="justify-content:space-between;">
            <h3>Recent Expenses</h3>
            <a href="{{ route('admin.finance.expenses.index') }}" class="btn btn-ghost" style="padding:6px 10px;font-size:11.5px;">All →</a>
        </div>
        @if ($recentExpenses->isEmpty())
            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">None yet.</div>
        @else
            <table>
                <tr><th>Number</th><th>Vendor</th><th>Total</th></tr>
                @foreach ($recentExpenses as $expense)
                    <tr>
                        <td><a href="{{ route('admin.finance.expenses.show', $expense) }}" style="color:var(--primary-dark);">{{ $expense->expense_number }}</a></td>
                        <td>{{ $expense->vendor->name ?? '—' }}</td>
                        <td>{{ number_format((float) $expense->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>
@endsection
