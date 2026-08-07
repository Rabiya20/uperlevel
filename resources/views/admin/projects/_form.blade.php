@php
    $s = fn ($field, $default = '') => old($field, $project->{$field} ?? $default);
@endphp

<div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div style="grid-column:1 / -1;">
        <label class="f-label">Project Name</label>
        <input class="f-input" type="text" name="name" value="{{ $s('name') }}" required>
    </div>

    <div>
        <label class="f-label">Client</label>
        <select class="f-input" name="client_id">
            <option value="">No client</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected($s('client_id') == $client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="f-label">Manager</label>
        <select class="f-input" name="manager_id">
            <option value="">Unassigned</option>
            @foreach ($managers as $manager)
                <option value="{{ $manager->id }}" @selected($s('manager_id') == $manager->id)>{{ $manager->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="f-label">Status</label>
        <select class="f-input" name="status">
            @foreach (\App\Http\Controllers\Admin\Projects\ProjectController::STATUSES as $status)
                <option value="{{ $status }}" @selected($s('status', 'planning') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="f-label">Budget</label>
        <input class="f-input" type="number" step="0.01" min="0" name="budget" value="{{ $s('budget') }}">
    </div>

    <div>
        <label class="f-label">Start Date</label>
        <input class="f-input" type="date" name="start_date" value="{{ $s('start_date') ? \Illuminate\Support\Carbon::parse($s('start_date'))->format('Y-m-d') : '' }}">
    </div>
    <div>
        <label class="f-label">End Date</label>
        <input class="f-input" type="date" name="end_date" value="{{ $s('end_date') ? \Illuminate\Support\Carbon::parse($s('end_date'))->format('Y-m-d') : '' }}">
    </div>

    <div style="grid-column:1 / -1;">
        <label class="f-label">Description</label>
        <textarea class="f-input" name="description" rows="4">{{ $s('description') }}</textarea>
    </div>

    <div style="grid-column:1 / -1;display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">{{ $project ? 'Save Changes' : 'Create Project' }}</button>
        <a href="{{ $project ? route('admin.projects.show', $project) : route('admin.projects.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
