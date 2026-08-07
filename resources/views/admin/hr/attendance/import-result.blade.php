@extends('layouts.admin')

@section('title', 'Import Results — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Import Results</h2>
        <p>{{ count($created) }} created, {{ count($updated) }} updated, {{ count($errors) }} skipped — of {{ $total }} row{{ $total === 1 ? '' : 's' }}.</p>
    </div>
    <a href="{{ route('admin.hr.attendance.import.index') }}" class="btn btn-primary">Back to Attendance Import</a>
</div>

@if (count($created) > 0)
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-head"><h3>Created ({{ count($created) }})</h3></div>
        <table>
            <tr><th>Row</th><th>Employee</th><th>Date</th><th>Status</th></tr>
            @foreach ($created as $entry)
                <tr>
                    <td>{{ $entry['row'] }}</td>
                    <td>{{ $entry['user']->name }}</td>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ ucfirst($entry['status']) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if (count($updated) > 0)
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-head"><h3>Updated ({{ count($updated) }})</h3></div>
        <table>
            <tr><th>Row</th><th>Employee</th><th>Date</th><th>Status</th></tr>
            @foreach ($updated as $entry)
                <tr>
                    <td>{{ $entry['row'] }}</td>
                    <td>{{ $entry['user']->name }}</td>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ ucfirst($entry['status']) }}</td>
                </tr>
            @endforeach
        </table>
        <p class="f-hint" style="padding:14px 20px 16px;margin:0;">These rows already had a record for that employee and date — it was corrected rather than duplicated.</p>
    </div>
@endif

@if (count($errors) > 0)
    <div class="panel">
        <div class="panel-head"><h3>Skipped ({{ count($errors) }})</h3></div>
        <table>
            <tr><th>Row</th><th>Email</th><th>Reason</th></tr>
            @foreach ($errors as $entry)
                <tr>
                    <td>{{ $entry['row'] }}</td>
                    <td>{{ $entry['email'] }}</td>
                    <td style="color:#C0392B;">{{ $entry['message'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if (count($created) === 0 && count($updated) === 0 && count($errors) === 0)
    <div class="panel">
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No rows found in that file.</div>
    </div>
@endif

<style>
    .f-hint{font-size:11px;color:var(--ink-soft);margin:0;}
</style>
@endsection
