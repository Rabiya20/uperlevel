@extends('layouts.admin')

@section('title', $account->code.' — '.$account->name.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $account->code }} — {{ $account->name }}</h2>
        <p><a href="{{ route('admin.finance.ledger.index') }}" style="color:var(--primary-dark);">← Back to Ledger</a></p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Voucher #, payee, description…">
        </div>
        <div>
            <label class="f-label">Transaction Type</label>
            <select class="f-input" name="transaction_type">
                <option value="">All</option>
                @foreach (['invoice' => 'Invoice', 'payment' => 'Payment', 'expense' => 'Expense', 'manual' => 'Manual'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('transaction_type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">From</label>
            <input class="f-input" type="date" name="from" value="{{ request('from') }}">
        </div>
        <div>
            <label class="f-label">To</label>
            <input class="f-input" type="date" name="to" value="{{ request('to') }}">
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@php $exportParams = request()->only(['q', 'transaction_type', 'from', 'to']); @endphp

<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>{{ $rows->count() }} transaction{{ $rows->count() === 1 ? '' : 's' }}</h3>
        <div class="export-dd" style="position:relative;">
            <button type="button" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;" onclick="this.nextElementSibling.classList.toggle('open')">Export ▾</button>
            <div class="export-dd-menu">
                <a href="{{ route('admin.finance.ledger.accounts.export', array_merge($exportParams, ['coa' => $account->id, 'format' => 'pdf'])) }}">⬇ Download PDF</a>
                <a href="{{ route('admin.finance.ledger.accounts.export', array_merge($exportParams, ['coa' => $account->id, 'format' => 'excel'])) }}">⬇ Download Excel</a>
                <a href="{{ route('admin.finance.ledger.accounts.export', array_merge($exportParams, ['coa' => $account->id, 'format' => 'print'])) }}" target="_blank">🖶 Print</a>
            </div>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <tr><th>Date</th><th>Voucher #</th><th>Type</th><th>Payee</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
            <tr style="background:var(--bg);font-weight:700;">
                <td colspan="7" style="text-align:right;">Opening Balance</td>
                <td>{{ $settings->formatMoney($openingBalance) }}</td>
            </tr>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['line']->journalEntry->entry_date->format('j M Y') }}</td>
                    <td>{{ $row['line']->journalEntry->reference_number ?? '—' }}</td>
                    <td>{{ $row['line']->journalEntry->transaction_type ? ucfirst($row['line']->journalEntry->transaction_type) : 'Manual' }}</td>
                    <td>{{ $row['line']->journalEntry->payee ?? '—' }}</td>
                    <td>{{ $row['line']->journalEntry->memo }}</td>
                    <td>{{ $row['line']->debit > 0 ? number_format((float) $row['line']->debit, 2) : '—' }}</td>
                    <td>{{ $row['line']->credit > 0 ? number_format((float) $row['line']->credit, 2) : '—' }}</td>
                    <td>{{ $settings->formatMoney($row['balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:var(--ink-soft);padding:24px 0;">No transactions in this range.</td></tr>
            @endforelse
            <tr style="background:var(--bg);font-weight:700;">
                <td colspan="7" style="text-align:right;">Closing Balance</td>
                <td>{{ $settings->formatMoney($closingBalance) }}</td>
            </tr>
        </table>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .export-dd-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:180px;z-index:20;overflow:hidden;}
    .export-dd-menu.open{display:block;}
    .export-dd-menu a{display:block;padding:10px 14px;font-size:12.5px;font-weight:600;color:var(--ink);text-decoration:none;}
    .export-dd-menu a:hover{background:var(--bg);}
</style>

<script>
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.export-dd-menu.open').forEach(function (menu) {
            if (!menu.parentElement.contains(e.target)) menu.classList.remove('open');
        });
    });
</script>
@endsection
