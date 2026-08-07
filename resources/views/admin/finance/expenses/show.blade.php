@extends('layouts.admin')

@section('title', $expense->expense_number.' — UperLevel')

@section('content-body')
@php
    $statusColors = [
        'draft' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'pending_approval' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'approved' => ['bg' => '#E8F0FE', 'fg' => '#1B54C4'],
        'paid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'cancelled' => ['bg' => '#FDEEEC', 'fg' => '#C0392B'],
    ];
    $color = $statusColors[$expense->status] ?? $statusColors['draft'];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $expense->expense_number }} <span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};margin-left:8px;">{{ ucfirst(str_replace('_', ' ', $expense->status)) }}</span></h2>
        <p><a href="{{ route('admin.finance.expenses.index') }}" style="color:var(--primary-dark);">← Back to Expenses</a></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @if ($expense->isEditable())
            <a href="{{ route('admin.finance.expenses.edit', $expense) }}" class="btn btn-ghost">Edit</a>
        @endif
        <form method="POST" action="{{ route('admin.finance.expenses.duplicate', $expense) }}">
            @csrf
            <button type="submit" class="btn btn-ghost">Duplicate</button>
        </form>
        @if ($expense->status === 'draft')
            <form method="POST" action="{{ route('admin.finance.expenses.submit', $expense) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Submit for Approval</button>
            </form>
        @endif
        @if ($expense->status === 'approved')
            <form method="POST" action="{{ route('admin.finance.expenses.mark-paid', $expense) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Mark as Paid</button>
            </form>
        @endif
        @if (! in_array($expense->status, ['paid', 'cancelled'], true))
            <form method="POST" action="{{ route('admin.finance.expenses.cancel', $expense) }}" onsubmit="return confirm('Cancel this expense?');">
                @csrf
                <button type="submit" class="btn btn-ghost">Cancel</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.finance.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense? This can\'t be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost" style="color:#c0392b;">Delete</button>
        </form>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if ($expense->status === 'pending_approval')
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FFF4E5;border-color:#FFF4E5;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
            <span style="color:#B4690E;font-weight:700;font-size:13px;">Waiting on approval before this expense is recorded.</span>
            <div style="display:flex;gap:10px;">
                <form method="POST" action="{{ route('admin.finance.expenses.approve', $expense) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.finance.expenses.reject', $expense) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="padding:8px 14px;font-size:12.5px;color:#c0392b;">Reject</button>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($expense->rejection_reason)
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        <span style="color:#C0392B;font-weight:700;font-size:13px;">Last rejected: {{ $expense->rejection_reason }}</span>
    </div>
@endif

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Details</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><span style="color:var(--ink-soft);">Vendor</span><div style="font-weight:600;">{{ $expense->vendor->name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Date</span><div style="font-weight:600;">{{ $expense->expense_date->format('j M Y') }}</div></div>
                <div><span style="color:var(--ink-soft);">Reference #</span><div style="font-weight:600;">{{ $expense->reference_number ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Project</span><div style="font-weight:600;">{{ $expense->project->name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Payment Account</span><div style="font-weight:600;">{{ $expense->paymentAccount->name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Payment Method</span><div style="font-weight:600;">{{ $expense->payment_method ? ucfirst(str_replace('_', ' ', $expense->payment_method)) : '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Payment Status</span><div style="font-weight:600;">{{ ucfirst($expense->payment_status) }}</div></div>
                <div><span style="color:var(--ink-soft);">Recurring</span><div style="font-weight:600;">{{ $expense->is_recurring ? ucfirst($expense->recurrence_interval).' — next '.$expense->next_occurrence_date?->format('j M Y') : 'No' }}</div></div>
                @if ($expense->description)
                    <div style="grid-column:1 / -1;"><span style="color:var(--ink-soft);">Memo</span><div>{{ $expense->description }}</div></div>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Line Items</h3></div>
            <div style="overflow-x:auto;">
                <table>
                    <tr><th>Category</th><th>Description</th><th>Customer</th><th>Project</th><th>Department</th><th>Class/Tag</th><th>Amount</th></tr>
                    @foreach ($expense->lines as $line)
                        <tr>
                            <td>{{ $line->category->name ?? '—' }}</td>
                            <td>{{ $line->description ?? '—' }}</td>
                            <td>{{ $line->client->name ?? '—' }}</td>
                            <td>{{ $line->project->name ?? '—' }}</td>
                            <td>{{ $line->department->name ?? '—' }}</td>
                            <td>{{ $line->class_tag ?? '—' }}</td>
                            <td>{{ number_format((float) $line->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr><td colspan="6" style="text-align:right;font-weight:600;">Subtotal</td><td>{{ number_format((float) $expense->subtotal, 2) }}</td></tr>
                    <tr><td colspan="6" style="text-align:right;font-weight:600;">Tax</td><td>{{ number_format((float) $expense->tax_amount, 2) }}</td></tr>
                    <tr><td colspan="6" style="text-align:right;font-weight:600;">Discount</td><td>−{{ number_format((float) $expense->discount_amount, 2) }}</td></tr>
                    <tr style="font-weight:700;"><td colspan="6" style="text-align:right;">Total</td><td>{{ number_format((float) $expense->total_amount, 2) }}</td></tr>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Attachments ({{ $expense->attachments->count() }})</h3></div>
            @if ($expense->attachments->isEmpty())
                <div style="padding:16px 20px;color:var(--ink-soft);font-size:12.5px;">No files attached.</div>
            @else
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:8px;">
                    @foreach ($expense->attachments as $attachment)
                        <a href="{{ route('admin.finance.expenses.attachments.download', [$expense, $attachment]) }}" style="font-size:12.5px;color:var(--primary-dark);">📎 {{ $attachment->original_filename }} <span style="color:var(--ink-soft);">— {{ $attachment->uploader->name ?? '—' }}, {{ $attachment->created_at->format('j M Y') }}</span></a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><h3>Audit Trail</h3></div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;font-size:12.5px;">
                <div><span style="color:var(--ink-soft);">Created by</span> — {{ $expense->creator->name ?? '—' }}, {{ $expense->created_at->format('j M Y, g:i A') }}</div>
                @if ($expense->updated_by)
                    <div><span style="color:var(--ink-soft);">Last edited by</span> — {{ $expense->updater->name ?? '—' }}, {{ $expense->updated_at->format('j M Y, g:i A') }}
                        @if ($expense->edit_reason)<div style="color:var(--ink-soft);font-style:italic;">"{{ $expense->edit_reason }}"</div>@endif
                    </div>
                @endif
                @if ($expense->approved_by)
                    <div><span style="color:var(--ink-soft);">{{ $expense->status === 'draft' ? 'Reviewed' : 'Approved' }} by</span> — {{ $expense->approver->name ?? '—' }}, {{ $expense->approved_at?->format('j M Y, g:i A') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
