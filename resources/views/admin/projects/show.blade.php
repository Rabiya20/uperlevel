@extends('layouts.admin')

@section('title', $project->name.' — Projects — UperLevel')

@section('content-body')
@php
    $statusColors = [
        'planning' => ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)'],
        'active' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'],
        'on_hold' => ['bg' => '#FFF4E5', 'fg' => '#B4690E'],
        'completed' => ['bg' => '#E8F0FE', 'fg' => '#1B54C4'],
        'archived' => ['bg' => '#F1EAFB', 'fg' => '#6C3FC5'],
    ];
    $color = $statusColors[$project->status] ?? $statusColors['planning'];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $project->name }} <span class="badge-pill" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};margin-left:8px;">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span></h2>
        <p><a href="{{ route('admin.projects.index') }}" style="color:var(--primary-dark);">← Back to Projects</a></p>
    </div>
    @if ($project->status !== 'archived')
        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-ghost">Edit</a>
    @endif
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

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Project Details</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><span style="color:var(--ink-soft);">Client</span><div style="font-weight:600;">
                    @if ($project->client)
                        <a href="{{ route('admin.company.clients.show', $project->client) }}" style="color:var(--primary-dark);">{{ $project->client->name }} →</a>
                    @else — @endif
                </div></div>
                <div><span style="color:var(--ink-soft);">Manager</span><div style="font-weight:600;">{{ $project->manager->name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Start Date</span><div style="font-weight:600;">{{ $project->start_date?->format('j M Y') ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">End Date</span><div style="font-weight:600;">{{ $project->end_date?->format('j M Y') ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Budget</span><div style="font-weight:600;">{{ $project->budget !== null ? number_format((float) $project->budget, 2) : '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Created By</span><div style="font-weight:600;">{{ $project->creator->name ?? '—' }}</div></div>
                @if ($project->description)
                    <div style="grid-column:1 / -1;"><span style="color:var(--ink-soft);">Description</span><div>{{ $project->description }}</div></div>
                @endif
            </div>
        </div>

        @if ($project->status !== 'archived')
            <div class="panel">
                <div class="panel-head" style="justify-content:space-between;">
                    <h3>Tasks</h3>
                    <a href="{{ route('admin.projects.tasks.index', $project) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Open Board →</a>
                </div>
                <div style="padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;">
                    @forelse (\App\Models\Task::STATUSES as $status)
                        <span class="badge-pill" style="background:var(--primary-soft);color:var(--primary-dark);">{{ ucfirst(str_replace('_', ' ', $status)) }}: {{ $taskCounts[$status] ?? 0 }}</span>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div class="panel-head" style="justify-content:space-between;">
                    <h3>Milestones ({{ $milestones->count() }})</h3>
                    <a href="{{ route('admin.projects.milestones.index', ['project_id' => $project->id]) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View All →</a>
                </div>
                @if ($milestones->isEmpty())
                    <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">No milestones yet.</div>
                @else
                    <table>
                        <tr><th>Title</th><th>Due</th><th>Status</th></tr>
                        @foreach ($milestones->take(5) as $milestone)
                            <tr>
                                <td>{{ $milestone->title }}</td>
                                <td>{{ $milestone->due_date?->format('j M Y') ?? '—' }}</td>
                                <td><span class="badge-pill" style="background:{{ $milestone->status === 'completed' ? '#E7F7EE' : 'var(--primary-soft)' }};color:{{ $milestone->status === 'completed' ? '#0F7C50' : 'var(--primary-dark)' }};">{{ ucfirst($milestone->status) }}</span></td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>

            <div class="panel">
                <div class="panel-head" style="justify-content:space-between;">
                    <h3>Timesheets</h3>
                    <a href="{{ route('admin.projects.timesheets.index', ['project_id' => $project->id]) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View All →</a>
                </div>
                <div style="padding:16px 20px;font-size:13px;">
                    <span style="color:var(--ink-soft);">Total logged hours</span>
                    <div style="font-weight:700;font-size:16px;">{{ number_format((float) $timesheetHours, 2) }}h</div>
                </div>
            </div>
        @endif

    </div>

    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Invoices ({{ $invoices->count() }})</h3></div>
            @if ($invoices->isEmpty())
                <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">No invoices linked to this project.</div>
            @else
                <table>
                    <tr><th>Number</th><th>Status</th><th>Total</th></tr>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('admin.finance.invoices.show', $invoice) }}" style="color:var(--primary-dark);">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                            <td>{{ number_format((float) $invoice->total, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Archive</h3></div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;font-size:12.5px;">
                @if ($project->status === 'archived')
                    <div style="background:#F1EAFB;border-radius:8px;padding:12px;color:#6C3FC5;">
                        Archived on {{ $project->archived_at->format('j M Y, g:i A') }} — its tasks, milestones and timesheets were exported and removed. Upload the archive file below to restore everything (finance/invoice history was never touched).
                    </div>
                    <form method="POST" action="{{ route('admin.projects.restore', $project) }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                        @csrf
                        <div style="flex:1;min-width:200px;">
                            <label class="f-label">Archive file (.zip)</label>
                            <input class="f-input" type="file" name="file" accept=".zip" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding:9px 16px;">Restore Project</button>
                    </form>
                @elseif ($project->isArchivable())
                    <div style="background:var(--success-soft);border-radius:8px;padding:12px;color:#0F7C50;">
                        This project is completed and every linked invoice is paid — it's ready to archive.
                    </div>
                    <form method="POST" action="{{ route('admin.projects.archive', $project) }}" onsubmit="return confirm('Archive this project? Its tasks, milestones and timesheets will be exported to a zip file and removed from live data. Invoices are never touched.');">
                        @csrf
                        <button type="submit" class="btn btn-primary">Archive & Download</button>
                    </form>
                @else
                    <div style="background:#FFF4E5;border-radius:8px;padding:12px;color:#B4690E;">
                        Not archivable yet — mark the project <strong>Completed</strong> and settle every linked invoice first.
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
