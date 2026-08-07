@extends('layouts.admin')

@section('title', 'Record Payment — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Record Payment</h2>
        <p><a href="{{ route('admin.finance.payments.index') }}" style="color:var(--primary-dark);">← Back to Payments</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($openInvoices->isEmpty())
    <div class="panel" style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No invoices currently have a balance due. <a href="{{ route('admin.finance.invoices.index') }}" style="color:var(--primary-dark);">View Invoices →</a>
    </div>
@else
    <div class="panel" style="max-width:640px;">
        <form method="POST" action="{{ route('admin.finance.payments.store') }}" style="padding:20px;" id="paymentForm">
            @csrf

            <div style="margin-bottom:14px;">
                <label class="f-label">Invoice</label>
                <select class="f-input" name="invoice_id" id="invoiceSelect" required>
                    <option value="">— Select —</option>
                    @foreach ($openInvoices as $invoice)
                        <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balanceDue() }}" @selected(old('invoice_id', $selectedInvoice?->id) == $invoice->id)>
                            {{ $invoice->invoice_number }} — {{ $invoice->client->name ?? '—' }} — Balance {{ $settings->formatMoney($invoice->balanceDue()) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label class="f-label">Amount</label>
                    <input class="f-input" type="number" step="0.01" min="0.01" name="amount" id="amountInput" value="{{ old('amount', $selectedInvoice?->balanceDue()) }}" required>
                </div>
                <div>
                    <label class="f-label">Date</label>
                    <input class="f-input" type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label class="f-label">Method</label>
                    <select class="f-input" name="payment_method" required>
                        @foreach ($methods as $m)
                            <option value="{{ $m }}" @selected(old('payment_method') === $m)>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Deposit To — optional</label>
                    <select class="f-input" name="deposit_account_id">
                        <option value="">Use default cash/bank account</option>
                        @foreach ($depositAccounts as $account)
                            <option value="{{ $account->id }}" @selected(old('deposit_account_id') == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label class="f-label">Reference Number — optional</label>
                <input class="f-input" type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="Cheque #, transaction ID…">
            </div>

            <div style="margin-bottom:18px;">
                <label class="f-label">Notes — optional</label>
                <textarea class="f-input" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>

            <div style="text-align:right;">
                <button type="submit" class="btn btn-primary">Record Payment</button>
            </div>
        </form>
    </div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>

<script>
    const invoiceSelect = document.getElementById('invoiceSelect');
    const amountInput = document.getElementById('amountInput');
    if (invoiceSelect) {
        invoiceSelect.addEventListener('change', () => {
            const option = invoiceSelect.selectedOptions[0];
            const balance = option ? parseFloat(option.dataset.balance) : null;
            if (balance) amountInput.value = balance.toFixed(2);
        });
    }
</script>
@endsection
