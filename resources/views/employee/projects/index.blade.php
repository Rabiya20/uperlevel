@extends('layouts.employee')

@section('title', 'Projects — UperLevel')
@section('page-title', 'Projects')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Projects</h2>
        <p>Projects you have tasks or logged time in.</p>
    </div>
</div>

<div class="panel">
    @if ($projects->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">You're not on any projects yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Client</th><th>Status</th><th></th></tr>
            @foreach ($projects as $project)
                <tr>
                    <td><strong>{{ $project->name }}</strong></td>
                    <td>{{ $project->client->name ?? '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</td>
                    <td><a href="{{ route('employee.projects.show', $project) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Open</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
