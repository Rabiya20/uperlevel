@extends('layouts.admin')

@section('title', 'Import Results — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Import Results</h2>
        <p>{{ count($created) }} of {{ $total }} row{{ $total === 1 ? '' : 's' }} imported successfully.</p>
    </div>
    <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-primary">Go to Employees</a>
</div>

@if (count($created) > 0)
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-head"><h3>Added ({{ count($created) }})</h3></div>
        <table>
            <tr><th>Name</th><th>Email</th><th>Temporary Password</th></tr>
            @foreach ($created as $entry)
                <tr>
                    <td>{{ $entry['employee']->name }}</td>
                    <td>{{ $entry['employee']->email }}</td>
                    <td><code style="background:var(--bg);padding:2px 8px;border-radius:4px;">{{ $entry['password'] }}</code></td>
                </tr>
            @endforeach
        </table>
        <p class="f-hint" style="padding:14px 20px 16px;margin:0;">Share these passwords with each employee now — they won't be shown again. They can be reset anytime from an employee's profile.</p>
    </div>
@endif

@if (count($errors) > 0)
    <div class="panel">
        <div class="panel-head"><h3>Skipped ({{ count($errors) }})</h3></div>
        <table>
            <tr><th>Row</th><th>Name</th><th>Reason</th></tr>
            @foreach ($errors as $entry)
                <tr>
                    <td>{{ $entry['row'] }}</td>
                    <td>{{ $entry['name'] }}</td>
                    <td style="color:#C0392B;">{{ $entry['message'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if (count($created) === 0 && count($errors) === 0)
    <div class="panel">
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No rows found in that file.</div>
    </div>
@endif

<style>
    .f-hint{font-size:11px;color:var(--ink-soft);margin:0;}
</style>
@endsection
