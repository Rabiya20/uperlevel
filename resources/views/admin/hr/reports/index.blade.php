@extends('layouts.admin')

@section('title', 'HR Reports — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>HR Reports</h2>
        <p>Every report can be downloaded as PDF or Excel, or sent straight to print.</p>
    </div>
</div>

<div class="reports-grid">
    <a href="{{ route('admin.hr.reports.attendance') }}" class="report-card">
        <div class="report-card-icon">🗓</div>
        <div class="report-card-title">Attendance Report</div>
        <p class="report-card-desc">Present, late, absent and leave day counts with worked/overtime hours, per person over a date range.</p>
    </a>

    <a href="{{ route('admin.hr.reports.employees') }}" class="report-card">
        <div class="report-card-icon">🧑‍💼</div>
        <div class="report-card-title">Employee Report</div>
        <p class="report-card-desc">Full roster — contact details, department, designation, role, status and shift.</p>
    </a>

    @if (auth()->user()->isOwnerOrAdmin() || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.hr.reports.payroll') }}" class="report-card">
            <div class="report-card-icon">💰</div>
            <div class="report-card-title">Payroll Structure Report</div>
            <p class="report-card-desc">Every employee's pay components, plus total payable, deductible and net.</p>
        </a>
    @endif

    <a href="{{ route('admin.hr.reports.leave') }}" class="report-card">
        <div class="report-card-icon">🏖</div>
        <div class="report-card-title">Leave Report</div>
        <p class="report-card-desc">Used and remaining balance per leave type, per person, for a calendar year.</p>
    </a>

    <a href="{{ route('admin.hr.reports.performance') }}" class="report-card">
        <div class="report-card-icon">📈</div>
        <div class="report-card-title">Performance Report</div>
        <p class="report-card-desc">Attendance rate, punctuality and average hours per day — derived from attendance records.</p>
    </a>
</div>

<style>
    .reports-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
    .report-card{display:block;background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;text-decoration:none;transition:border-color .15s,box-shadow .15s;}
    .report-card:hover{border-color:var(--primary);box-shadow:0 4px 16px rgba(0,0,0,.06);}
    .report-card-icon{font-size:22px;margin-bottom:8px;}
    .report-card-title{font-size:14.5px;font-weight:700;color:var(--ink);margin-bottom:6px;}
    .report-card-desc{font-size:12px;color:var(--ink-soft);margin:0;line-height:1.5;}
</style>
@endsection
