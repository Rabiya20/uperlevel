@extends('layouts.admin')

@section('title', 'Payroll Structure Report — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Payroll Structure Report</h2>
        <p><a href="{{ route('admin.hr.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a> — current pay component assignments.</p>
    </div>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'Payroll Structure',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.hr.reports.payroll.export',
    'exportParams' => [],
])
@endsection
