@extends('layouts.admin')

@section('title', 'Projects — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Projects</h2>
        <p>{{ $projects->total() }} project{{ $projects->total() === 1 ? '' : 's' }}.</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">+ New Project</a>
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
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                @foreach (array_merge(\App\Http\Controllers\Admin\Projects\ProjectController::STATUSES, ['archived']) as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Client</label>
            <select class="f-input" name="client_id">
                <option value="">All</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Manager</label>
            <select class="f-input" name="manager_id">
                <option value="">All</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(request('manager_id') == $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@php
    $statusColors = [
        'planning' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'active' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'on_hold' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'completed' => ['bg' => '#E8F0FE', 'fg' => '#1B54C4'],
        'archived' => ['bg' => '#F1EAFB', 'fg' => '#6C3FC5'],
    ];
@endphp

<div class="panel">
    @if ($projects->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No projects yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Client</th><th>Manager</th><th>Status</th><th>Timeline</th><th></th></tr>
            @foreach ($projects as $project)
                @php $color = $statusColors[$project->status] ?? $statusColors['planning']; @endphp
                <tr>
                    <td><strong>{{ $project->name }}</strong></td>
                    <td>{{ $project->client->name ?? '—' }}</td>
                    <td>{{ $project->manager->name ?? '—' }}</td>
                    <td><span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span></td>
                    <td>{{ $project->start_date?->format('j M Y') ?? '—' }} – {{ $project->end_date?->format('j M Y') ?? '—' }}</td>
                    <td><a href="{{ route('admin.projects.show', $project) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Open</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($projects->hasPages())
    <div style="margin-top:16px;">{{ $projects->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
