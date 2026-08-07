@extends('layouts.admin')

@section('title', 'Milestones — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Milestones</h2>
        <p>{{ $milestones->total() }} milestone{{ $milestones->total() === 1 ? '' : 's' }} across all projects.</p>
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
    <div class="panel-head"><h3>+ Add Milestone</h3></div>
    <form method="POST" action="{{ route('admin.projects.milestones.store') }}" style="padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
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
        <div style="flex:1;min-width:180px;">
            <label class="f-label">Title</label>
            <input class="f-input" type="text" name="title" required>
        </div>
        <div>
            <label class="f-label">Due Date</label>
            <input class="f-input" type="date" name="due_date">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:9px 16px;">Add</button>
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
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

<div class="panel">
    @if ($milestones->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No milestones found.</div>
    @else
        <table>
            <tr><th>Title</th><th>Project</th><th>Due</th><th>Status</th><th></th></tr>
            @foreach ($milestones as $milestone)
                <tr>
                    <td>{{ $milestone->title }}</td>
                    <td><a href="{{ route('admin.projects.show', $milestone->project) }}" style="color:var(--primary-dark);">{{ $milestone->project->name }}</a></td>
                    <td>{{ $milestone->due_date?->format('j M Y') ?? '—' }}</td>
                    <td><span class="badge-pill" style="background:{{ $milestone->status === 'completed' ? '#E7F7EE' : 'var(--primary-soft)' }};color:{{ $milestone->status === 'completed' ? '#0F7C50' : 'var(--primary-dark)' }};">{{ ucfirst($milestone->status) }}</span></td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            @if ($milestone->status !== 'completed')
                                <form method="POST" action="{{ route('admin.projects.milestones.complete', $milestone) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Mark Complete</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.projects.milestones.destroy', $milestone) }}" onsubmit="return confirm('Remove this milestone?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($milestones->hasPages())
    <div style="margin-top:16px;">{{ $milestones->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
