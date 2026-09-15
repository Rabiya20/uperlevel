@extends('layouts.admin')

@section('title', 'Employee Report — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Employee Report</h2>
        <p><a href="{{ route('admin.hr.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a></p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">{{ session('status') }}</span>
    </div>
@endif

<div style="display:flex;gap:8px;margin-bottom:18px;">
    <a href="{{ route('admin.hr.reports.employees') }}" class="btn {{ !$archived ? 'btn-primary' : 'btn-ghost' }}">Active Employees</a>
    <a href="{{ route('admin.hr.reports.employees', ['archived' => 1]) }}" class="btn {{ $archived ? 'btn-primary' : 'btn-ghost' }}">Archive</a>
</div>

@include('admin.hr.reports._report-table', [
    'title' => $archived ? 'Archived Employees' : 'All Employees',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.hr.reports.employees.export',
    'exportParams' => ['archived' => $archived ? 1 : 0],
    'rowIds' => $rowIds,
    'archiveRoute' => $archived ? null : 'admin.hr.reports.employees.archive',
])
@endsection
