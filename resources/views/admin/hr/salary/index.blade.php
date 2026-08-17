@extends('layouts.admin')

@section('title', 'Salary — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Salary</h2>
        <p>Base salary for every employee — visible only to roles granted access here.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="panel">
    @if ($employees->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No employees yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Designation</th><th>Department</th><th>Basic Salary</th><th></th></tr>
            @foreach ($employees as $employee)
                <tr>
                    <td>
                        <strong>{{ $employee->name }}</strong>
                        <div style="font-size:11px;color:var(--ink-soft);text-transform:capitalize;">{{ $employee->role }}</div>
                    </td>
                    <td>{{ $employee->designation->name ?? '—' }}</td>
                    <td>{{ $employee->department->name ?? '—' }}</td>
                    <td>{{ $employee->basic_salary !== null ? number_format((float) $employee->basic_salary, 2) : '—' }}</td>
                    <td><a href="{{ route('admin.hr.employees.salary.edit', $employee) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View / Edit</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
