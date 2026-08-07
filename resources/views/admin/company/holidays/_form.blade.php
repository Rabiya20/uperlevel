@php
    $s = fn ($field, $default = '') => old($field, $holiday->{$field} ?? $default);
@endphp

<div class="panel" style="max-width:480px;">
    <div class="panel-head"><h3>Holiday Details</h3></div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
        <div>
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="120" placeholder="e.g. Independence Day" required>
        </div>
        <div>
            <label class="f-label">Date</label>
            <input class="f-input" type="date" name="date" value="{{ $holiday && $holiday->date ? $holiday->date->format('Y-m-d') : old('date') }}" required>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Holiday</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
</style>
