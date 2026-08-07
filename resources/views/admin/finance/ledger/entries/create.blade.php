@extends('layouts.admin')

@section('title', 'New Journal Entry — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>New Journal Entry</h2>
        <p><a href="{{ route('admin.finance.ledger.entries.index') }}" style="color:var(--primary-dark);">← Back to Journal Entries</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($accounts->isEmpty())
    <div class="panel" style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No accounts yet. <a href="{{ route('admin.finance.coa.index') }}" style="color:var(--primary-dark);">Set up your Chart of Accounts →</a>
    </div>
@else
    <div class="panel">
        <form method="POST" action="{{ route('admin.finance.ledger.entries.store') }}" id="entryForm" style="padding:20px;">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
                <div>
                    <label class="f-label">Date</label>
                    <input class="f-input" type="date" name="entry_date" value="{{ old('entry_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="f-label">Memo</label>
                    <input class="f-input" type="text" name="memo" value="{{ old('memo') }}" placeholder="What is this entry for?" required>
                </div>
            </div>

            <div id="lineRows" style="display:flex;flex-direction:column;gap:10px;"></div>
            <button type="button" class="btn btn-ghost" id="addLineRow" style="margin-top:12px;padding:7px 12px;font-size:12.5px;">+ Add line</button>

            <div style="display:flex;justify-content:flex-end;gap:24px;margin-top:16px;padding-top:16px;border-top:1px solid var(--line);font-size:13px;font-weight:700;">
                <span>Total Debit: <span id="totalDebit">0.00</span></span>
                <span>Total Credit: <span id="totalCredit">0.00</span></span>
                <span id="balanceIndicator" style="color:#C0392B;">Unbalanced</span>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Post Entry</button>
                <a href="{{ route('admin.finance.ledger.entries.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .line-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;}
</style>

<script>
    const ACCOUNTS = @json($accounts->map(fn ($a) => ['id' => $a->id, 'label' => $a->code.' — '.$a->name]));
    const accountOptionsHtml = '<option value="">Select account…</option>' + ACCOUNTS.map(a => `<option value="${a.id}">${a.label}</option>`).join('');

    const lineRows = document.getElementById('lineRows');
    let lineIndex = 0;

    function recalcTotals() {
        let debit = 0, credit = 0;
        document.querySelectorAll('input[name$="[debit]"]').forEach(i => debit += parseFloat(i.value) || 0);
        document.querySelectorAll('input[name$="[credit]"]').forEach(i => credit += parseFloat(i.value) || 0);
        document.getElementById('totalDebit').textContent = debit.toFixed(2);
        document.getElementById('totalCredit').textContent = credit.toFixed(2);
        const indicator = document.getElementById('balanceIndicator');
        const balanced = Math.abs(debit - credit) < 0.005 && debit > 0;
        indicator.textContent = balanced ? 'Balanced ✓' : 'Unbalanced';
        indicator.style.color = balanced ? '#0F7C50' : '#C0392B';
    }

    function addLineRow() {
        const i = lineIndex++;
        const row = document.createElement('div');
        row.className = 'line-row';
        row.innerHTML = `
            <div>
                <label class="f-label">Account</label>
                <select class="f-input" name="lines[${i}][chart_of_account_id]" required>${accountOptionsHtml}</select>
            </div>
            <div>
                <label class="f-label">Debit</label>
                <input class="f-input" type="number" step="0.01" min="0" name="lines[${i}][debit]" value="0">
            </div>
            <div>
                <label class="f-label">Credit</label>
                <input class="f-input" type="number" step="0.01" min="0" name="lines[${i}][credit]" value="0">
            </div>
            <button type="button" class="btn btn-ghost" style="padding:9px 12px;">✕</button>
        `;
        const debitInput = row.querySelector('input[name$="[debit]"]');
        const creditInput = row.querySelector('input[name$="[credit]"]');
        // A single line is either a debit or a credit, never both — typing
        // into one clears the other rather than letting both sit non-zero.
        debitInput.addEventListener('input', () => { if (parseFloat(debitInput.value) > 0) creditInput.value = 0; recalcTotals(); });
        creditInput.addEventListener('input', () => { if (parseFloat(creditInput.value) > 0) debitInput.value = 0; recalcTotals(); });
        row.querySelector('button').addEventListener('click', () => { row.remove(); recalcTotals(); });
        lineRows.appendChild(row);
    }

    document.getElementById('addLineRow').addEventListener('click', addLineRow);
    addLineRow();
    addLineRow();
</script>
@endsection
