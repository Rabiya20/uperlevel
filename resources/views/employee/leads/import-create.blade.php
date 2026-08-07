@extends('layouts.employee')

@section('title', 'Import Leads — UperLevel')
@section('page-title', 'Import Leads')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Import Leads</h2>
        <p><a href="{{ route('employee.leads.index') }}" style="color:var(--primary-dark);">← Back to My Leads</a></p>
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
            Have old leads in a spreadsheet? Download the template, fill it in, and upload it here. Only the <strong>Name</strong> column is required.
        </p>

        <div style="background:#FFF4E5;border-radius:8px;padding:12px;font-size:12.5px;color:#B4690E;margin-bottom:18px;">
            Your upload won't become real leads right away — an admin reviews and approves it first.
            You can track its status under <a href="{{ route('employee.leads.imports.index') }}" style="color:#B4690E;font-weight:700;">Import History</a>.
        </div>

        <a href="{{ route('employee.leads.import.template') }}" class="btn btn-ghost" style="margin-bottom:18px;">⬇ Download sample template</a>

        <form method="POST" action="{{ route('employee.leads.import.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="f-label">Spreadsheet file (.xlsx, .xls or .csv — max 5MB)</label>
            <input class="f-input" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            <div style="margin-top:16px;text-align:right;">
                <button type="submit" class="btn btn-primary">Upload for Review</button>
            </div>
        </form>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
