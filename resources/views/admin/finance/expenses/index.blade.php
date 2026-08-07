@extends('layouts.admin')

@section('title', 'Expenses — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Expenses</h2>
        <p>{{ $expenses->total() }} expense{{ $expenses->total() === 1 ? '' : 's' }}.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.finance.vendors.index') }}" class="btn btn-ghost">Vendors</a>
        <a href="{{ route('admin.finance.expense-categories.index') }}" class="btn btn-ghost">Categories</a>
        <a href="{{ route('admin.finance.expenses.create') }}" class="btn btn-primary">+ Record Expense</a>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Expense #, vendor, memo…">
        </div>
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                @foreach (\App\Models\Expense::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Vendor</label>
            <select class="f-input" name="vendor_id">
                <option value="">All</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Payment Account</label>
            <select class="f-input" name="payment_account_id">
                <option value="">All</option>
                @foreach ($paymentAccounts as $account)
                    <option value="{{ $account->id }}" @selected(request('payment_account_id') == $account->id)>{{ $account->name }}</option>
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

@php
    $statusColors = [
        'draft' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'pending_approval' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'approved' => ['bg' => '#E8F0FE', 'fg' => '#1B54C4'],
        'paid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'cancelled' => ['bg' => '#FDEEEC', 'fg' => '#C0392B'],
    ];
    $exportParams = request()->only(['q', 'status', 'vendor_id', 'payment_account_id', 'from', 'to']);
@endphp

<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>{{ $expenses->total() }} expense{{ $expenses->total() === 1 ? '' : 's' }}</h3>
        <div class="export-dd" style="position:relative;">
            <button type="button" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;" onclick="this.nextElementSibling.classList.toggle('open')">Export ▾</button>
            <div class="export-dd-menu">
                <a href="{{ route('admin.finance.expenses.export', array_merge($exportParams, ['format' => 'pdf'])) }}">⬇ Download PDF</a>
                <a href="{{ route('admin.finance.expenses.export', array_merge($exportParams, ['format' => 'excel'])) }}">⬇ Download Excel</a>
                <a href="{{ route('admin.finance.expenses.export', array_merge($exportParams, ['format' => 'print'])) }}" target="_blank">🖶 Print</a>
            </div>
        </div>
    </div>
    @if ($expenses->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No expenses found.</div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <tr><th>Date</th><th>Expense No.</th><th>Vendor</th><th>Payment Account</th><th>Total</th><th>Status</th><th>Created By</th><th></th></tr>
                @foreach ($expenses as $expense)
                    @php $color = $statusColors[$expense->status] ?? $statusColors['draft']; @endphp
                    <tr>
                        <td>{{ $expense->expense_date->format('j M Y') }}</td>
                        <td style="font-weight:700;">{{ $expense->expense_number }}</td>
                        <td>{{ $expense->vendor->name ?? '—' }}</td>
                        <td>{{ $expense->paymentAccount->name ?? '—' }}</td>
                        <td>{{ number_format((float) $expense->total_amount, 2) }}</td>
                        <td><span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};">{{ ucfirst(str_replace('_', ' ', $expense->status)) }}</span></td>
                        <td>{{ $expense->creator->name ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="{{ route('admin.finance.expenses.show', $expense) }}" class="btn btn-ghost" style="padding:6px 10px;font-size:12px;">View</a>
                                @if ($expense->isEditable())
                                    <a href="{{ route('admin.finance.expenses.edit', $expense) }}" class="btn btn-ghost" style="padding:6px 10px;font-size:12px;">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</div>

@if ($expenses->hasPages())
    <div style="margin-top:16px;">{{ $expenses->links() }}</div>
@endif

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
