@extends('layouts.admin')

@section('title', 'Timesheets — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Timesheets</h2>
        <p>{{ $timesheets->total() }} logged entr{{ $timesheets->total() === 1 ? 'y' : 'ies' }} across all projects.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="panel" style="margin-bottom:18px;">
    <div class="panel-head"><h3>Log Time</h3></div>
    <form method="POST" action="{{ route('admin.projects.timesheets.store') }}" style="padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div>
            <label class="f-label">Project</label>
            <select class="f-input" name="project_id" required>
                <option value="">Select…</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Person</label>
            <select class="f-input" name="user_id">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($user->id === auth()->id())>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Date</label>
            <input class="f-input" type="date" name="date" value="{{ now()->format('Y-m-d') }}" required>
        </div>
        <div>
            <label class="f-label">Hours</label>
            <input class="f-input" type="number" step="0.25" min="0.25" max="24" name="hours" required>
        </div>
        <div style="flex:1;min-width:180px;">
            <label class="f-label">Notes</label>
            <input class="f-input" type="text" name="notes">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:9px 16px;">Log Time</button>
    </form>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Project</label>
            <select class="f-input" name="project_id">
                <option value="">All</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Person</label>
            <select class="f-input" name="user_id">
                <option value="">All</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

<div class="panel">
    @if ($timesheets->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No time logged yet.</div>
    @else
        <table>
            <tr><th>Date</th><th>Project</th><th>Person</th><th>Hours</th><th>Notes</th><th></th></tr>
            @foreach ($timesheets as $entry)
                <tr>
                    <td>{{ $entry->date->format('j M Y') }}</td>
                    <td><a href="{{ route('admin.projects.show', $entry->project) }}" style="color:var(--primary-dark);">{{ $entry->project->name }}</a></td>
                    <td>{{ $entry->user->name ?? '—' }}</td>
                    <td>{{ number_format((float) $entry->hours, 2) }}</td>
                    <td>{{ $entry->notes ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.projects.timesheets.destroy', $entry) }}" onsubmit="return confirm('Remove this entry?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($timesheets->hasPages())
    <div style="margin-top:16px;">{{ $timesheets->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
