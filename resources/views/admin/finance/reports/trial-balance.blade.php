@extends('layouts.admin')

@section('title', 'Trial Balance — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Trial Balance</h2>
        <p><a href="{{ route('admin.finance.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a> — as of {{ now()->format('M j, Y') }}</p>
    </div>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'Accounts',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.finance.reports.trial-balance.export',
    'exportParams' => [],
])
@endsection
