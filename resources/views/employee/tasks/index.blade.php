@extends('layouts.employee')

@section('title', 'My Tasks — UperLevel')
@section('page-title', 'My Tasks')

@section('content-body')
<div class="page-head">
    <div>
        <h2>My Tasks</h2>
        <p>Every task assigned to you, across all your projects.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@php $columns = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done']; @endphp

<div class="panel">
    @if ($tasks->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">Nothing assigned to you right now.</div>
    @else
        <table>
            <tr><th>Task</th><th>Project</th><th>Due</th><th>Priority</th><th>Status</th></tr>
            @foreach ($columns as $key => $label)
                @foreach (($tasks[$key] ?? collect()) as $task)
                    <tr>
                        <td>{{ $task->title }}</td>
                        <td>{{ $task->project->name ?? '—' }}</td>
                        <td>{{ $task->due_date?->format('j M Y') ?? '—' }}</td>
                        <td>{{ ucfirst($task->priority) }}</td>
                        <td>
                            <form method="POST" action="{{ route('employee.tasks.update-status', $task) }}">
                                @csrf
                                @method('PUT')
                                <select class="f-input f-input-inline" name="status" onchange="this.form.submit()">
                                    @foreach ($columns as $sKey => $sLabel)
                                        <option value="{{ $sKey }}" @selected($sKey === $task->status)>{{ $sLabel }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @endif
</div>

<style>
    .f-input{padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input-inline{padding:6px 8px;font-size:12.5px;min-width:130px;}
</style>
@endsection
