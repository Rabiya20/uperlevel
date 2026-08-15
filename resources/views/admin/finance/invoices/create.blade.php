@extends('layouts.admin')

@section('title', 'Create Invoice — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Create Invoice</h2>
        <p><a href="{{ route('admin.finance.invoices.index') }}" style="color:var(--primary-dark);">← Back to Invoices</a> — will be issued as <strong>{{ $nextNumber }}</strong>.</p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($clients->isEmpty())
    <div class="panel" style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No clients yet — clients are added automatically when a lead is won. <a href="{{ route('admin.company.clients.index') }}" style="color:var(--primary-dark);">View Clients →</a>
    </div>
@else
    <div class="panel">
        <form method="POST" action="{{ route('admin.finance.invoices.store') }}" style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            @csrf

            <div>
                <label class="f-label">Client</label>
                <select class="f-input" name="client_id" id="invoiceClient" required>
                    <option value="">Select a client…</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $selectedClientId) == $client->id)>{{ $client->name }}@if ($client->company_name) ({{ $client->company_name }})@endif</option>
                    @endforeach
                </select>
                @if ($clientLeadData->isNotEmpty())
                    <p style="font-size:11px;color:var(--ink-soft);margin:6px 0 0;">Currency and amount below are filled in automatically from the client's original lead, where one exists — feel free to adjust.</p>
                @endif
            </div>
            <div></div>

            <div>
                <label class="f-label">Issue Date</label>
                <input class="f-input" type="date" name="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="f-label">Due Date</label>
                <input class="f-input" type="date" name="due_date" value="{{ old('due_date', now()->addDays($settings->default_payment_terms_days ?? 14)->format('Y-m-d')) }}" required>
            </div>

            <div>
                <label class="f-label">Currency</label>
                <select class="f-input" name="currency" id="invoiceCurrency">
                    @foreach ($currencyOptions as $code)
                        @php $c = config("currencies.{$code}", ['name' => $code, 'symbol' => $code]); @endphp
                        <option value="{{ $code }}" @selected(old('currency', $settings->currency_code) === $code)>{{ $code }} — {{ $c['name'] }} ({{ $c['symbol'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="f-label">Amount</label>
                <input class="f-input" type="number" step="0.01" min="0" name="subtotal" id="invoiceAmount" value="{{ old('subtotal') }}" required>
            </div>

            <div style="display:flex;align-items:center;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
                    <input type="checkbox" name="apply_tax" value="1" @checked(old('apply_tax'))>
                    Apply {{ $settings->tax_label ?? 'Tax' }} ({{ $settings->tax_percentage }}%)
                </label>
            </div>
            <div></div>

            <div style="grid-column:1 / -1;">
                <label class="f-label">Notes</label>
                <textarea class="f-input" name="notes" rows="3">{{ old('notes', $settings->invoice_notes_default) }}</textarea>
            </div>

            <div style="grid-column:1 / -1;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Create Invoice</button>
                <a href="{{ route('admin.finance.invoices.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>

@if ($clientLeadData->isNotEmpty())
<script>
    (function () {
        const clientLeadData = @json($clientLeadData);
        const clientSelect = document.getElementById('invoiceClient');
        const currencySelect = document.getElementById('invoiceCurrency');
        const amountInput = document.getElementById('invoiceAmount');

        function applyLeadDefaults() {
            const data = clientLeadData[clientSelect.value];
            if (! data) {
                return;
            }
            if (data.currency && Array.from(currencySelect.options).some(o => o.value === data.currency)) {
                currencySelect.value = data.currency;
            }
            if (data.budget !== null && data.budget !== undefined) {
                amountInput.value = data.budget;
            }
        }

        clientSelect.addEventListener('change', applyLeadDefaults);

        // Only auto-fill on load for a fresh visit (e.g. arriving via a
        // client's "+ Create Invoice" link) — never clobber values the user
        // already typed on a redisplay after a validation error.
        @if (! $errors->any() && old('subtotal') === null)
            applyLeadDefaults();
        @endif
    })();
</script>
@endif
@endsection
