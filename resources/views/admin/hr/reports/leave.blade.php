@extends('layouts.admin')

@section('title', 'Leave Report — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Leave Report</h2>
        <p><a href="{{ route('admin.hr.reports.index') }}" style="color:var(--primary-dark);">← Back to Reports</a> — calendar year {{ $year }}.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Year</label>
            <select class="f-input" name="year">
                @foreach (range(now()->year, now()->year - 4) as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@include('admin.hr.reports._report-table', [
    'title' => 'Leave Balances',
    'headers' => $headers,
    'rows' => $rows,
    'exportRoute' => 'admin.hr.reports.leave.export',
    'exportParams' => ['year' => $year],
])

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
