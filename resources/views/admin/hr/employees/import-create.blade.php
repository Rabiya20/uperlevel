@extends('layouts.admin')

@section('title', 'Import Employees — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Import Employees</h2>
        <p><a href="{{ route('admin.hr.employees.index') }}" style="color:var(--primary-dark);">← Back to Employees</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="panel" style="max-width:560px;">
    <div class="panel-head"><h3>Upload a spreadsheet</h3></div>
    <div style="padding:18px 20px;">
        <p style="font-size:13px;color:var(--ink-soft);margin:0 0 14px;">
            Already have your team's data in a spreadsheet? Download the template below, fill it in, and upload it here.
            Only <strong>Name</strong> and <strong>Email</strong> are required — everything else is optional.
            A temporary login password is generated for each new employee and shown to you once the import finishes.
        </p>

        <a href="{{ route('admin.hr.employees.import.template') }}" class="btn btn-ghost" style="margin-bottom:18px;">⬇ Download sample template</a>

        <form method="POST" action="{{ route('admin.hr.employees.import.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="f-label">Spreadsheet file (.xlsx, .xls or .csv — max 5MB)</label>
            <input class="f-input" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            <div style="margin-top:16px;text-align:right;">
                <button type="submit" class="btn btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
