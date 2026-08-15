@extends('layouts.admin')

@section('title', $project->name.' Board — UperLevel')

@section('content-body')
@php
    $columns = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
    $priorityColors = ['low' => '#0F7C50', 'medium' => '#B4690E', 'high' => '#C0392B'];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $project->name }} — Board</h2>
        <p><a href="{{ route('admin.projects.show', $project) }}" style="color:var(--primary-dark);">← Back to Project</a></p>
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
    <div class="panel-head"><h3>+ Add Task</h3></div>
    <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}" style="padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="flex:1;min-width:180px;">
            <label class="f-label">Title</label>
            <input class="f-input" type="text" name="title" required>
        </div>
        <div>
            <label class="f-label">Assignee</label>
            <select class="f-input" name="assigned_to">
                <option value="">Unassigned</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Priority</label>
            <select class="f-input" name="priority">
                @foreach (\App\Models\Task::PRIORITIES as $p)
                    <option value="{{ $p }}" @selected($p === 'medium')>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Due Date</label>
            <input class="f-input" type="date" name="due_date">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:9px 16px;">Add Task</button>
    </form>
</div>

<div class="kanban-grid">
    @foreach ($columns as $key => $label)
        <div class="kanban-col">
            <div class="kanban-col-head">{{ $label }} ({{ ($tasks[$key] ?? collect())->count() }})</div>
            @forelse (($tasks[$key] ?? collect()) as $task)
                <div class="kanban-card">
                    <div class="kanban-card-title">{{ $task->title }}</div>
                    @if ($task->description)
                        <p class="kanban-card-desc">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</p>
                    @endif
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                        <span style="font-size:11px;color:{{ $priorityColors[$task->priority] }};font-weight:700;text-transform:uppercase;">{{ $task->priority }}</span>
                        @if ($task->due_date)
                            <span style="font-size:11px;color:var(--ink-soft);">Due {{ $task->due_date->format('j M') }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}" style="margin-top:8px;">
                        @csrf
                        @method('PUT')
                        <select class="f-input f-input-inline" name="assigned_to" onchange="this.form.submit()" title="Reassign this task">
                            <option value="">Unassigned</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected($assignee->id === $task->assigned_to)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}" style="margin-top:6px;">
                        @csrf
                        @method('PUT')
                        <select class="f-input f-input-inline" name="status" onchange="this.form.submit()">
                            @foreach ($columns as $sKey => $sLabel)
                                <option value="{{ $sKey }}" @selected($sKey === $task->status)>{{ $sLabel }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @empty
                <div class="kanban-empty">No tasks.</div>
            @endforelse
        </div>
    @endforeach
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input-inline{padding:6px 8px;font-size:12px;}
    .kanban-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;align-items:start;}
    .kanban-col{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:12px;min-height:120px;}
    .kanban-col-head{font-size:12.5px;font-weight:700;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.02em;margin-bottom:10px;}
    .kanban-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px;margin-bottom:10px;}
    .kanban-card-title{font-size:13px;font-weight:700;color:var(--ink);}
    .kanban-card-desc{font-size:11.5px;color:var(--ink-soft);margin:4px 0 0;}
    .kanban-empty{font-size:12px;color:var(--ink-soft);text-align:center;padding:12px 0;}
    @media (max-width: 900px){ .kanban-grid{grid-template-columns:1fr 1fr;} }
</style>
@endsection
