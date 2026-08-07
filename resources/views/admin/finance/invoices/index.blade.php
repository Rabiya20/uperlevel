@extends('layouts.admin')

@section('title', 'Invoices — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Invoices</h2>
        <p>Billed against clients won from the Leads pipeline.</p>
    </div>
    <a href="{{ route('admin.finance.invoices.create') }}" class="btn btn-primary">+ Create Invoice</a>
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

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                @foreach (['draft', 'sent', 'paid', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@php
    $statusColors = [
        'draft' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'sent' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'paid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'cancelled' => ['bg' => '#FDEEEC', 'fg' => '#C0392B'],
    ];
@endphp

<div class="panel">
    <div class="panel-head"><h3>{{ $invoices->total() }} invoice{{ $invoices->total() === 1 ? '' : 's' }}</h3></div>
    @if ($invoices->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No invoices yet.</div>
    @else
        <table>
            <tr><th>Number</th><th>Client</th><th>Issue Date</th><th>Due Date</th><th>Total</th><th>Balance Due</th><th>Status</th><th></th></tr>
            @foreach ($invoices as $invoice)
                @php $color = $statusColors[$invoice->status] ?? $statusColors['draft']; @endphp
                <tr>
                    <td style="font-weight:700;">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->client->name ?? '—' }}</td>
                    <td>{{ $invoice->issue_date->format('j M Y') }}</td>
                    <td>{{ $invoice->due_date->format('j M Y') }}</td>
                    <td>{{ number_format((float) $invoice->total, 2) }}</td>
                    <td>{{ $invoice->status === 'cancelled' ? '—' : number_format($invoice->balanceDue(), 2) }}</td>
                    <td>
                        <span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};">{{ ucfirst($invoice->status) }}</span>
                        @if ($invoice->isOverdue($settings->invoice_overdue_grace_days))
                            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Overdue</span>
                        @endif
                    </td>
                    <td><a href="{{ route('admin.finance.invoices.show', $invoice) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($invoices->hasPages())
    <div style="margin-top:16px;">{{ $invoices->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
