@extends('layouts.employee')

@section('title', $project->name.' — UperLevel')
@section('page-title', $project->name)

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $project->name }}</h2>
        <p><a href="{{ route('employee.projects.index') }}" style="color:var(--primary-dark);">← Back to Projects</a></p>
    </div>
</div>

<div class="grid-2" style="align-items:start;">
    <div class="panel">
        <div class="panel-head"><h3>My Tasks Here</h3></div>
        @if ($tasks->isEmpty())
            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">No tasks assigned to you on this project.</div>
        @else
            <table>
                <tr><th>Title</th><th>Status</th><th>Due</th></tr>
                @foreach ($tasks as $task)
                    <tr>
                        <td>{{ $task->title }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</td>
                        <td>{{ $task->due_date?->format('j M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head"><h3>Milestones</h3></div>
        @if ($milestones->isEmpty())
            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">No milestones yet.</div>
        @else
            <table>
                <tr><th>Title</th><th>Due</th><th>Status</th></tr>
                @foreach ($milestones as $milestone)
                    <tr>
                        <td>{{ $milestone->title }}</td>
                        <td>{{ $milestone->due_date?->format('j M Y') ?? '—' }}</td>
                        <td>{{ ucfirst($milestone->status) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>
@endsection
