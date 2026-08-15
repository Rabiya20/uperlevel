@extends('layouts.admin')

@section('title', 'Boards — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Boards</h2>
        <p>Pick a project to add tasks, assign work to team members, and track progress.</p>
    </div>
</div>

@if ($projects->isEmpty())
    <div class="panel" style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No active projects yet. <a href="{{ route('admin.projects.create') }}" style="color:var(--primary-dark);">Create one →</a>
    </div>
@else
    <div class="board-grid">
        @foreach ($projects as $project)
            <a href="{{ route('admin.projects.tasks.index', $project) }}" class="board-card">
                <div class="board-card-title">{{ $project->name }}</div>
                <p class="board-card-desc">{{ $project->tasks_count }} task{{ $project->tasks_count === 1 ? '' : 's' }} · {{ ucfirst(str_replace('_', ' ', $project->status)) }}</p>
            </a>
        @endforeach
    </div>
@endif

<style>
    .board-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;}
    .board-card{display:block;background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px;text-decoration:none;transition:border-color .15s,box-shadow .15s;}
    .board-card:hover{border-color:var(--primary);box-shadow:0 4px 16px rgba(0,0,0,.06);}
    .board-card-title{font-size:14.5px;font-weight:700;color:var(--ink);margin-bottom:6px;}
    .board-card-desc{font-size:12px;color:var(--ink-soft);margin:0;}
</style>
@endsection
