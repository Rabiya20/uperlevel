@extends('layouts.admin')

@section('title', 'Attendance Report — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Attendance Report</h2>
        <p><a href="{{ route('admin.hr.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a> — {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">From</label>
            <input class="f-input" type="date" name="from" value="{{ $start->format('Y-m-d') }}">
        </div>
        <div>
            <label class="f-label">To</label>
            <input class="f-input" type="date" name="to" value="{{ $end->format('Y-m-d') }}">
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'Team Members',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.hr.reports.attendance.export',
    'exportParams' => ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d')],
])

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
