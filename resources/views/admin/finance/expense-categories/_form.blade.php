@php
    $s = fn ($field, $default = '') => old($field, $category->{$field} ?? $default);
@endphp

<div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;max-width:480px;">
    <div>
        <label class="f-label">Name</label>
        <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="120" placeholder="e.g. Software, Marketing" required>
    </div>
    <div>
        <label class="f-label">Posts to account — optional</label>
        <select class="f-input" name="chart_of_account_id">
            <option value="">Use ledger default expense account</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected($s('chart_of_account_id') == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </select>
        <p class="f-hint">When the ledger auto-posts an approved expense in this category, it debits this account instead of the tenant-wide default.</p>
    </div>
    <label class="f-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
        Active
    </label>
    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">{{ $category ? 'Save Changes' : 'Add Category' }}</button>
        <a href="{{ route('admin.finance.expense-categories.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
