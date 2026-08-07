@php
    $s = fn ($field, $default = '') => old($field, $designation->{$field} ?? $default);
@endphp

<div class="panel" style="max-width:480px;">
    <div class="panel-head"><h3>Designation Details</h3></div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
        <div>
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="80" placeholder="e.g. Video Editor, Founder & CEO" required>
        </div>
        <div>
            <label class="f-label">Department</label>
            <select class="f-input" name="department_id">
                <option value="">—</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) $s('department_id') === $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="f-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
            Active
        </label>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Designation</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
