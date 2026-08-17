@extends('layouts.admin')

@section('title', $employee->name.' — Salary — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $employee->name }}'s Salary</h2>
        <p><a href="{{ route('admin.hr.employees.show', $employee) }}" style="color:var(--primary-dark);">← Back to Profile</a></p>
    </div>
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

<div class="panel" style="max-width:420px;">
    <div class="panel-head"><h3>Basic Salary</h3></div>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.hr.employees.salary.update', $employee) }}" style="padding:18px 20px;">
            @csrf
            @method('PUT')
            <label class="f-label">Basic salary</label>
            <input class="f-input" type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}">
            <div style="margin-top:16px;text-align:right;">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    @else
        <div style="padding:18px 20px;">
            <div class="f-label">Basic salary</div>
            <div style="font-size:15px;font-weight:700;">{{ $employee->basic_salary !== null ? number_format((float) $employee->basic_salary, 2) : '—' }}</div>
            <p class="f-hint">You have view-only access to salary — ask an admin to grant "Edit" on the Salary module in Company → User Role if you need to change this.</p>
        </div>
    @endif
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:8px 0 0;}
</style>
@endsection
