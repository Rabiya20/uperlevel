@extends('layouts.admin')

@section('title', 'Chart of Accounts — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Chart of Accounts</h2>
        <p><a href="{{ route('admin.finance.settings') }}" style="color:var(--primary-dark);">← Back to Finance Setup</a></p>
    </div>
    <div style="display:flex;gap:10px;">
        @if ($accounts->isEmpty())
            <form method="POST" action="{{ route('admin.finance.coa.seed-defaults') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">Load default chart of accounts</button>
            </form>
        @endif
        <a href="{{ route('admin.finance.coa.create') }}" class="btn btn-primary">+ Add Account</a>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@php
    $typeLabels = ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];
    $typeColors = [
        'asset' => ['bg' => '#E8F0FE', 'fg' => '#1B54C4'],
        'liability' => ['bg' => '#FDEEEC', 'fg' => '#C0392B'],
        'equity' => ['bg' => '#F1EAFB', 'fg' => '#6C3FC5'],
        'income' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'expense' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
    ];
    $grouped = $accounts->groupBy('type');
@endphp

<div class="panel">
    <div class="panel-head"><h3>All accounts ({{ $accounts->count() }})</h3></div>

    @if ($accounts->isEmpty())
        <div style="padding:32px 20px;text-align:center;">
            <p style="color:var(--ink-soft);font-size:13.5px;margin:0 0 14px;">No accounts yet. Start from the standard chart of accounts, or add your own.</p>
        </div>
    @else
        <table>
            <tr><th>Code</th><th>Name</th><th>Type</th><th>Parent</th><th>Expense Categories</th><th>Opening Balance</th><th>Status</th><th></th></tr>
            @foreach ($typeLabels as $type => $label)
                @foreach ($grouped->get($type, []) as $account)
                    <tr>
                        <td style="font-weight:700;">{{ $account->code }}</td>
                        <td>{{ $account->name }}</td>
                        <td>
                            <span class="badge-pill" style="background:{{ $typeColors[$type]['bg'] }};color:{{ $typeColors[$type]['fg'] }};">{{ $label }}</span>
                        </td>
                        <td>{{ $account->parent?->name ?? '—' }}</td>
                        <td>{{ $account->expenseCategories->isNotEmpty() ? $account->expenseCategories->pluck('name')->join(', ') : '—' }}</td>
                        <td>{{ number_format((float) $account->opening_balance, 2) }}</td>
                        <td>
                            @if ($account->is_active)
                                <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                            @else
                                <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:8px;">
                                <a href="{{ route('admin.finance.coa.edit', $account) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                                <form method="POST" action="{{ route('admin.finance.coa.destroy', $account) }}" onsubmit="return confirm('Remove this account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @endif
</div>
@endsection
