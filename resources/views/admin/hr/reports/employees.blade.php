@extends('layouts.admin')

@section('title', 'Employee Report — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Employee Report</h2>
        <p><a href="{{ route('admin.hr.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a></p>
    </div>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'All Employees',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.hr.reports.employees.export',
    'exportParams' => [],
])
@endsection
