@php
    $s = fn ($field, $default = '') => old($field, $shift->{$field} ?? $default);
@endphp

<div class="panel" style="max-width:560px;">
    <div class="panel-head"><h3>Shift Details</h3></div>
    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="grid-column:1 / -1;">
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="60" required>
        </div>
        <div>
            <label class="f-label">Start time</label>
            <input class="f-input" type="time" name="start_time" value="{{ $shift ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : old('start_time', '09:00') }}" required>
        </div>
        <div>
            <label class="f-label">End time</label>
            <input class="f-input" type="time" name="end_time" value="{{ $shift ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : old('end_time', '17:00') }}" required>
            <p class="f-hint">End before start (e.g. 22:00 → 06:00) is treated as an overnight shift.</p>
        </div>
        <div>
            <label class="f-label">Grace period (minutes)</label>
            <input class="f-input" type="number" min="0" max="120" name="grace_minutes" value="{{ $s('grace_minutes', 0) }}" required>
            <p class="f-hint">How late someone can check in before it counts as "Late".</p>
        </div>
        <div>
            <label class="f-label">Break (minutes)</label>
            <input class="f-input" type="number" min="0" max="240" name="break_minutes" value="{{ $s('break_minutes', 0) }}" required>
            <p class="f-hint">Subtracted from worked hours.</p>
        </div>
        <div style="grid-column:1 / -1;display:flex;gap:20px;">
            <label class="f-check">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked($s('is_default', false))>
                Default shift for new employees
            </label>
            <label class="f-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
                Active
            </label>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Shift</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
