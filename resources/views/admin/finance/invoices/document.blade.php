<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
    *{box-sizing:border-box} body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2933;font-size:12px;margin:0}.document{padding:36px;max-width:820px;margin:auto}.header{display:flex;justify-content:space-between;border-bottom:3px solid #159a8c;padding-bottom:20px;margin-bottom:28px}.brand{font-size:22px;font-weight:700;color:#087f73}.muted{color:#68737d}.title{text-align:right}.title h1{margin:0;font-size:25px;color:#1f2933}.title p{margin:6px 0 0;color:#68737d}.columns{display:flex;justify-content:space-between;margin-bottom:28px}.label{font-size:10px;text-transform:uppercase;color:#68737d;font-weight:700;margin-bottom:5px}.value{font-weight:600}.box{background:#f4f8f7;border:1px solid #dce9e6;padding:14px;border-radius:6px}.summary{margin-left:auto;width:260px;margin-top:22px}.summary div{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e1e7e5}.summary .total{font-size:16px;font-weight:700;border-bottom:0;padding-top:12px}.notes{margin-top:28px;padding-top:14px;border-top:1px solid #dce2e0}.footer{margin-top:48px;text-align:center;border-top:1px solid #dce2e0;padding-top:12px;color:#87918e;font-size:10px}@media print{.document{padding:0}.no-print{display:none}}
</style>
</head>
<body>
<div class="document">
    <div class="header">
        <div><div class="brand">{{ $invoice->tenant->name ?? 'Company' }}</div><div class="muted">Powered by UperLevel</div></div>
        <div class="title"><h1>INVOICE</h1><p>{{ $invoice->invoice_number }}</p></div>
    </div>
    <div class="columns">
        <div><div class="label">Bill To</div><div class="value">{{ $invoice->client->name ?? '—' }}</div><div class="muted">{{ $invoice->client->email ?? '' }}</div></div>
        <div><div class="label">Issue Date</div><div class="value">{{ $invoice->issue_date->format('j M Y') }}</div><div class="label" style="margin-top:14px">Due Date</div><div class="value">{{ $invoice->due_date->format('j M Y') }}</div></div>
        <div><div class="label">Status</div><div class="box value">{{ ucfirst($invoice->status) }}</div></div>
    </div>
    <div class="box">
        <div class="columns" style="margin:0"><div><div class="label">Description</div><div class="value">Professional services</div></div><div><div class="label">Currency</div><div class="value">{{ $invoice->currency }}</div></div></div>
    </div>
    <div class="summary">
        <div><span>Subtotal</span><span>{{ $settings->formatMoney($invoice->subtotal, $invoice->currency) }}</span></div>
        <div><span>Tax ({{ $invoice->tax_percentage }}%)</span><span>{{ $settings->formatMoney($invoice->tax_amount, $invoice->currency) }}</span></div>
        <div class="total"><span>Total</span><span>{{ $settings->formatMoney($invoice->total, $invoice->currency) }}</span></div>
        <div><span>Amount paid</span><span>{{ $settings->formatMoney($invoice->amountPaid(), $invoice->currency) }}</span></div>
        <div><span>Balance due</span><span>{{ $settings->formatMoney($invoice->balanceDue(), $invoice->currency) }}</span></div>
    </div>
    @if ($invoice->notes)<div class="notes"><div class="label">Notes</div>{{ $invoice->notes }}</div>@endif
    <div class="footer">{{ $invoice->tenant->name ?? 'Company' }} · Generated {{ now()->format('j M Y g:i A') }}</div>
</div>
@if (($mode ?? 'pdf') === 'print')<script>window.onload=function(){window.print();};</script>@endif
</body>
</html>
