<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $expense->expense_number }}</title>
<style>
    *{box-sizing:border-box} body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2933;font-size:11px;margin:0}.document{padding:36px;max-width:820px;margin:auto}.header{display:flex;justify-content:space-between;border-bottom:3px solid #159a8c;padding-bottom:20px;margin-bottom:26px}.brand{font-size:22px;font-weight:700;color:#087f73}.muted{color:#68737d}.title{text-align:right}.title h1{margin:0;font-size:24px}.title p{margin:6px 0;color:#68737d}.columns{display:flex;justify-content:space-between;margin-bottom:22px}.label{font-size:9px;text-transform:uppercase;color:#68737d;font-weight:700;margin-bottom:5px}.value{font-weight:600}.box{background:#f4f8f7;border:1px solid #dce9e6;padding:13px;border-radius:6px}.items{width:100%;border-collapse:collapse;margin-top:24px}.items th{background:#087f73;color:#fff;text-align:left;padding:9px 8px;font-size:9px;text-transform:uppercase}.items td{padding:9px 8px;border-bottom:1px solid #e2e8e6}.items td:last-child,.items th:last-child{text-align:right}.totals{margin:18px 0 0 auto;width:260px}.totals div{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e1e7e5}.totals .total{font-size:16px;font-weight:700;border-bottom:0;padding-top:12px}.notes{margin-top:25px;padding-top:14px;border-top:1px solid #dce2e0}.footer{margin-top:45px;text-align:center;border-top:1px solid #dce2e0;padding-top:12px;color:#87918e;font-size:10px}@media print{.document{padding:0}}
</style>
</head>
<body>
<div class="document">
    <div class="header">
        <div><div class="brand">{{ $expense->tenant->name ?? 'Company' }}</div><div class="muted">Powered by UperLevel</div></div>
        <div class="title"><h1>EXPENSE</h1><p>{{ $expense->expense_number }}</p></div>
    </div>
    <div class="columns">
        <div><div class="label">Vendor</div><div class="value">{{ $expense->vendor->name ?? '—' }}</div></div>
        <div><div class="label">Expense Date</div><div class="value">{{ $expense->expense_date->format('j M Y') }}</div></div>
        <div><div class="label">Payment Account</div><div class="value">{{ $expense->paymentAccount->name ?? '—' }}</div></div>
        <div><div class="label">Status</div><div class="box value">{{ ucfirst(str_replace('_', ' ', $expense->status)) }}</div></div>
    </div>
    @if ($expense->description)<div class="box"><div class="label">Memo</div>{{ $expense->description }}</div>@endif
    <table class="items">
        <tr><th>Category</th><th>Description</th><th>Customer</th><th>Project</th><th>Amount</th></tr>
        @foreach ($expense->lines as $line)
            <tr><td>{{ $line->category->name ?? '—' }}</td><td>{{ $line->description ?? '—' }}</td><td>{{ $line->client->name ?? '—' }}</td><td>{{ $line->project->name ?? '—' }}</td><td>{{ number_format((float) $line->amount, 2) }}</td></tr>
        @endforeach
    </table>
    <div class="totals">
        <div><span>Subtotal</span><span>{{ number_format((float) $expense->subtotal, 2) }}</span></div>
        <div><span>Tax</span><span>{{ number_format((float) $expense->tax_amount, 2) }}</span></div>
        <div><span>Discount</span><span>-{{ number_format((float) $expense->discount_amount, 2) }}</span></div>
        <div class="total"><span>Total</span><span>{{ number_format((float) $expense->total_amount, 2) }}</span></div>
    </div>
    <div class="footer">{{ $expense->tenant->name ?? 'Company' }} · Generated {{ now()->format('j M Y g:i A') }}</div>
</div>
@if (($mode ?? 'pdf') === 'print')<script>window.onload=function(){window.print();};</script>@endif
</body>
</html>
