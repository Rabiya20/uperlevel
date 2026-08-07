@php
    $s = fn ($field, $default = '') => old($field, $vendor->{$field} ?? $default);
@endphp

<div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;max-width:480px;">
    <div>
        <label class="f-label">Name</label>
        <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="150" required>
    </div>
    <div>
        <label class="f-label">Email — optional</label>
        <input class="f-input" type="email" name="email" value="{{ $s('email') }}" maxlength="190">
    </div>
    <div>
        <label class="f-label">Phone — optional</label>
        <input class="f-input" type="text" name="phone" value="{{ $s('phone') }}" maxlength="30">
    </div>
    <div>
        <label class="f-label">Address — optional</label>
        <textarea class="f-input" name="address" rows="3">{{ $s('address') }}</textarea>
    </div>
    <label class="f-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
        Active
    </label>
    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">{{ $vendor ? 'Save Changes' : 'Add Vendor' }}</button>
        <a href="{{ route('admin.finance.vendors.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
