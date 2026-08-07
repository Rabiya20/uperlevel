@php
    $isAdmin = $isAdmin ?? false;
@endphp

<div class="panel" style="max-width:640px;">
    <div class="panel-head"><h3>Lead Details</h3></div>
    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="grid-column:1 / -1;">
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" value="{{ old('name', $lead->name ?? '') }}" maxlength="120" required>
        </div>
        <div>
            <label class="f-label">Company name</label>
            <input class="f-input" type="text" name="company_name" value="{{ old('company_name', $lead->company_name ?? '') }}" maxlength="120">
        </div>
        <div>
            <label class="f-label">Source</label>
            <select class="f-input" name="source">
                <option value="">—</option>
                @foreach ($settings->lead_sources ?? [] as $source)
                    <option value="{{ $source }}" @selected(old('source', $lead->source ?? '') === $source)>{{ $source }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Email</label>
            <input class="f-input" type="email" name="email" value="{{ old('email', $lead->email ?? '') }}" maxlength="150">
        </div>
        <div>
            <label class="f-label">Phone</label>
            <input class="f-input" type="text" name="phone" value="{{ old('phone', $lead->phone ?? '') }}" maxlength="30">
        </div>
        <div>
            <label class="f-label">Country</label>
            <input class="f-input" type="text" name="country" value="{{ old('country', $lead->country ?? '') }}" maxlength="60">
        </div>
        <div>
            <label class="f-label">Budget</label>
            <input class="f-input" type="number" step="0.01" min="0" name="budget" value="{{ old('budget', $lead->budget ?? '') }}">
        </div>
        @if ($isAdmin)
            <div>
                <label class="f-label">Assign to</label>
                <select class="f-input" name="assigned_employee_id">
                    <option value="">Unassigned</option>
                    @foreach ($employees ?? [] as $employee)
                        <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $lead->assigned_employee_id ?? '') == $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div style="grid-column:1 / -1;">
            <label class="f-label">Description</label>
            <textarea class="f-input" name="description" rows="4" placeholder="Brief, requirements, anything worth capturing up front.">{{ old('description', $lead->description ?? '') }}</textarea>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">{{ isset($lead) && $lead ? 'Save Lead' : 'Add Lead' }}</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
</style>
