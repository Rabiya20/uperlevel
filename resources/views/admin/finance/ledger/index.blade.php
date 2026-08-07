@extends('layouts.admin')

@section('title', 'Ledger — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Ledger</h2>
        <p>Live account balances — opening balance plus every posted journal entry.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.finance.coa.index') }}" class="btn btn-ghost">Chart of Accounts</a>
        <a href="{{ route('admin.finance.ledger.entries.index') }}" class="btn btn-ghost">Journal Entries</a>
        <a href="{{ route('admin.finance.ledger.entries.create') }}" class="btn btn-primary">+ New Entry</a>
    </div>
</div>

@unless ($settings->ledger_enabled)
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FFF4E5;border-color:#FFF4E5;">
        <span style="color:#B4690E;font-weight:700;font-size:13px;">The ledger is currently disabled — enable it under Finance → Setup → Ledger to start posting entries.</span>
    </div>
@endunless

@php
    $typeLabels = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expenses'];
@endphp

@if ($accounts->isEmpty())
    <div class="panel" style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No accounts yet. <a href="{{ route('admin.finance.coa.index') }}" style="color:var(--primary-dark);">Set up your Chart of Accounts →</a>
    </div>
@else
    @foreach ($typeLabels as $type => $label)
        @if ($accounts->has($type))
            <div class="panel" style="margin-bottom:18px;">
                <div class="panel-head"><h3>{{ $label }}</h3></div>
                <table>
                    <tr><th>Code</th><th>Name</th><th>Current Balance</th><th></th></tr>
                    @foreach ($accounts[$type] as $account)
                        <tr>
                            <td style="font-weight:700;">{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $settings->formatMoney($account->currentBalance()) }}</td>
                            <td><a href="{{ route('admin.finance.ledger.accounts.show', $account) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View Ledger →</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    @endforeach
@endif
@endsection
