@extends('layouts.admin')

@section('title', 'Revenue & AR Aging — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Revenue & AR Aging</h2>
        <p><a href="{{ route('admin.finance.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a> — all non-draft invoices</p>
    </div>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'Invoices',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.finance.reports.revenue.export',
    'exportParams' => [],
])
@endsection
