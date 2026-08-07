@php
    $typeLabels = ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];
@endphp

<div class="panel" style="max-width:640px;">
    <div class="panel-head"><h3>Account Details</h3></div>
    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div>
            <label class="f-label">Code</label>
            <input class="f-input" type="text" name="code" value="{{ old('code', $account->code ?? '') }}" maxlength="20" required>
        </div>
        <div>
            <label class="f-label">Type</label>
            <select class="f-input" name="type" required>
                <option value="">Select type…</option>
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected(old('type', $account->type ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="grid-column:1 / -1;">
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" value="{{ old('name', $account->name ?? '') }}" maxlength="120" required>
        </div>
        <div>
            <label class="f-label">Parent account — optional</label>
            <select class="f-input" name="parent_id">
                <option value="">None</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $account->parent_id ?? '') == $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Opening balance</label>
            <input class="f-input" type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}">
        </div>
        <div style="grid-column:1 / -1;">
            <label class="f-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))>
                Active — available for use on invoices and ledger entries
            </label>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Account</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
