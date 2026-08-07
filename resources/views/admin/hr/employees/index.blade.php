@extends('layouts.admin')

@section('title', 'Employees — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Employees</h2>
        <p>{{ $employees->total() }} team member{{ $employees->total() === 1 ? '' : 's' }} on record.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.hr.employees.import.create') }}" class="btn btn-ghost">Import from Excel</a>
        <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">+ Add Employee</a>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if (session('generated_password'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FFF4E5;border-color:#FFF4E5;">
        <span style="color:#B4690E;font-weight:700;font-size:13px;">
            Temporary password: <code style="background:#fff;padding:2px 8px;border-radius:4px;">{{ session('generated_password') }}</code>
            — share it with them now, it won't be shown again.
        </span>
    </div>
@endif

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="search" value="{{ request('search') }}" placeholder="Name, email or code">
        </div>
        <div>
            <label class="f-label">Department</label>
            <select class="f-input" name="department">
                <option value="">All</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((int) request('department') === $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                @foreach (\App\Models\User::EMPLOYMENT_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
        @if (request()->anyFilled(['search', 'department', 'status']))
            <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-ghost" style="padding:9px 16px;">Clear</a>
        @endif
    </form>
</div>

<div class="panel">
    @if ($employees->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No employees found.</div>
    @else
        <table>
            <tr><th>Name</th><th>Role</th><th>Department</th><th>Shift</th><th>Status</th><th></th></tr>
            @foreach ($employees as $employee)
                <tr>
                    <td>
                        <strong>{{ $employee->name }}</strong>
                        <div style="font-size:11px;color:var(--ink-soft);">{{ $employee->email }}</div>
                    </td>
                    <td style="text-transform:capitalize;">{{ $employee->role }}</td>
                    <td>{{ $employee->department->name ?? '—' }}</td>
                    <td>{{ $employee->shift->name ?? '—' }}</td>
                    <td>
                        @if ($employee->employment_status === 'active')
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @elseif ($employee->employment_status === 'on_leave')
                            <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">On Leave</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Terminated</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a>
                            <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($employees->hasPages())
    <div style="margin-top:16px;">{{ $employees->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
