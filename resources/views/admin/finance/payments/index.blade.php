@extends('layouts.admin')

@section('title', 'Payments — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Payments</h2>
        <p>{{ $payments->total() }} payment{{ $payments->total() === 1 ? '' : 's' }} recorded against invoices.</p>
    </div>
    <a href="{{ route('admin.finance.payments.create') }}" class="btn btn-primary">+ Record Payment</a>
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
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Invoice #, client, reference…">
        </div>
        <div>
            <label class="f-label">Client</label>
            <select class="f-input" name="client_id">
                <option value="">All</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Method</label>
            <select class="f-input" name="payment_method">
                <option value="">All</option>
                @foreach (\App\Models\Payment::METHODS as $m)
                    <option value="{{ $m }}" @selected(request('payment_method') === $m)>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
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

@php $exportParams = request()->only(['q', 'client_id', 'payment_method', 'from', 'to']); @endphp

<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>{{ $payments->total() }} payment{{ $payments->total() === 1 ? '' : 's' }}</h3>
        <div class="export-dd" style="position:relative;">
            <button type="button" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;" onclick="this.nextElementSibling.classList.toggle('open')">Export ▾</button>
            <div class="export-dd-menu">
                <a href="{{ route('admin.finance.payments.export', array_merge($exportParams, ['format' => 'pdf'])) }}">⬇ Download PDF</a>
                <a href="{{ route('admin.finance.payments.export', array_merge($exportParams, ['format' => 'excel'])) }}">⬇ Download Excel</a>
                <a href="{{ route('admin.finance.payments.export', array_merge($exportParams, ['format' => 'print'])) }}" target="_blank">🖶 Print</a>
            </div>
        </div>
    </div>
    @if ($payments->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No payments recorded yet.</div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <tr><th>Date</th><th>Invoice #</th><th>Client</th><th>Amount</th><th>Method</th><th>Reference</th><th></th></tr>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('j M Y') }}</td>
                        <td style="font-weight:700;">{{ $payment->invoice->invoice_number ?? '—' }}</td>
                        <td>{{ $payment->client->name ?? '—' }}</td>
                        <td>{{ $settings->formatMoney($payment->amount) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td>
                            @if ($payment->invoice)
                                <a href="{{ route('admin.finance.invoices.show', $payment->invoice) }}" class="btn btn-ghost" style="padding:6px 10px;font-size:12px;">View Invoice</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</div>

@if ($payments->hasPages())
    <div style="margin-top:16px;">{{ $payments->links() }}</div>
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
