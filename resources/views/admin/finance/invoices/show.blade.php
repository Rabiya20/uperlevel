@extends('layouts.admin')

@section('title', $invoice->invoice_number.' — Invoices — UperLevel')

@section('content-body')
@php
    $statusColors = [
        'draft' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'sent' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'paid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'cancelled' => ['bg' => '#FDEEEC', 'fg' => '#C0392B'],
    ];
    $color = $statusColors[$invoice->status] ?? $statusColors['draft'];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $invoice->invoice_number }}</h2>
        <p><a href="{{ route('admin.finance.invoices.index') }}" style="color:var(--primary-dark);">← Back to Invoices</a></p>
    </div>
    <div style="display:flex;gap:10px;">
        @if ($invoice->status === 'draft')
            <form method="POST" action="{{ route('admin.finance.invoices.mark-sent', $invoice) }}">
                @csrf
                <button type="submit" class="btn btn-ghost">Mark as Sent</button>
            </form>
        @endif
        @if (in_array($invoice->status, ['draft', 'sent'], true))
            @if ($invoice->balanceDue() > 0)
                <a href="{{ route('admin.finance.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary">Record Payment</a>
            @endif
            <form method="POST" action="{{ route('admin.finance.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?');">
                @csrf
                <button type="submit" class="btn btn-ghost" style="color:#c0392b;">Cancel</button>
            </form>
        @endif
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>Invoice Details</h3>
        <span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};">{{ ucfirst($invoice->status) }}</span>
        @if ($invoice->isOverdue($settings->invoice_overdue_grace_days))
            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Overdue</span>
        @endif
    </div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
        <div><span style="color:var(--ink-soft);">Client</span><div style="font-weight:600;"><a href="{{ route('admin.company.clients.show', $invoice->client) }}" style="color:var(--primary-dark);">{{ $invoice->client->name }} →</a></div></div>
        <div><span style="color:var(--ink-soft);">Created by</span><div style="font-weight:600;">{{ $invoice->creator->name ?? '—' }}</div></div>
        <div><span style="color:var(--ink-soft);">Issue Date</span><div style="font-weight:600;">{{ $invoice->issue_date->format('j M Y') }}</div></div>
        <div><span style="color:var(--ink-soft);">Due Date</span><div style="font-weight:600;">{{ $invoice->due_date->format('j M Y') }}</div></div>
        <div><span style="color:var(--ink-soft);">Currency</span><div style="font-weight:600;">{{ $invoice->currency }}</div></div>
        <div><span style="color:var(--ink-soft);">Subtotal</span><div style="font-weight:600;">{{ $settings->formatMoney($invoice->subtotal, $invoice->currency) }}</div></div>
        <div><span style="color:var(--ink-soft);">Tax ({{ $invoice->tax_percentage }}%)</span><div style="font-weight:600;">{{ $settings->formatMoney($invoice->tax_amount, $invoice->currency) }}</div></div>
        <div><span style="color:var(--ink-soft);">Total</span><div style="font-weight:700;font-size:15px;">{{ $settings->formatMoney($invoice->total, $invoice->currency) }}</div></div>
        <div><span style="color:var(--ink-soft);">Amount Paid</span><div style="font-weight:600;color:#0F7C50;">{{ $settings->formatMoney($invoice->amountPaid(), $invoice->currency) }}</div></div>
        <div><span style="color:var(--ink-soft);">Balance Due</span><div style="font-weight:700;font-size:15px;{{ $invoice->balanceDue() > 0 ? 'color:#C0392B;' : '' }}">{{ $settings->formatMoney($invoice->balanceDue(), $invoice->currency) }}</div></div>
        @if ($invoice->notes)
            <div style="grid-column:1 / -1;"><span style="color:var(--ink-soft);">Notes</span><div>{{ $invoice->notes }}</div></div>
        @endif
    </div>
</div>

<div class="panel" style="margin-top:18px;">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>Payments</h3>
        @if ($invoice->balanceDue() > 0 && in_array($invoice->status, ['draft', 'sent'], true))
            <a href="{{ route('admin.finance.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">+ Record Payment</a>
        @endif
    </div>
    @if ($invoice->payments->isEmpty())
        <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No payments recorded yet.</div>
    @else
        <table>
            <tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Received By</th></tr>
            @foreach ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('j M Y') }}</td>
                    <td>{{ $settings->formatMoney($payment->amount, $invoice->currency) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td>{{ $payment->reference_number ?? '—' }}</td>
                    <td>{{ $payment->receiver->name ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
