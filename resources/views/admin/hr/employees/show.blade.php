@extends('layouts.admin')

@section('title', $employee->name.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $employee->name }}</h2>
        <p>{{ $employee->designation->name ?? ucfirst($employee->role) }}{{ $employee->department ? ' — '.$employee->department->name : '' }}</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.hr.attendance.show', $employee) }}" class="btn btn-ghost">Attendance</a>
        <a href="{{ route('admin.hr.leaves.show', $employee) }}" class="btn btn-ghost">Leave History</a>
        @if (auth()->user()->isOwnerOrAdmin() || auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.hr.employees.payroll.edit', $employee) }}" class="btn btn-ghost">Payroll Structure</a>
        @endif
        <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-primary">Edit</a>
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

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Basic Details</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13.5px;">
                <div><div class="f-label">Email</div>{{ $employee->email }}</div>
                <div><div class="f-label">Phone</div>{{ $employee->phone ?? '—' }}</div>
                <div><div class="f-label">Employee code</div>{{ $employee->employee_code ?? '—' }}</div>
                <div><div class="f-label">Gender</div>{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</div>
                <div><div class="f-label">Date of birth</div>{{ optional($employee->date_of_birth)->format('j M Y') ?? '—' }}</div>
                <div><div class="f-label">CNIC / National ID</div>{{ $employee->cnic ?? '—' }}</div>
                <div style="grid-column:1 / -1;"><div class="f-label">Address</div>{{ $employee->address ?? '—' }}</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Emergency Contact</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13.5px;">
                <div><div class="f-label">Name</div>{{ $employee->emergency_contact_name ?? '—' }}</div>
                <div><div class="f-label">Phone</div>{{ $employee->emergency_contact_phone ?? '—' }}</div>
            </div>
        </div>

    </div>

    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Employment</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13.5px;">
                <div><div class="f-label">Role</div><span style="text-transform:capitalize;">{{ $employee->role }}</span></div>
                <div><div class="f-label">Access role</div>{{ $employee->customRole->name ?? '— (base role only)' }}</div>
                <div>
                    <div class="f-label">Status</div>
                    @if ($employee->employment_status === 'active')
                        <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                    @elseif ($employee->employment_status === 'on_leave')
                        <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">On Leave</span>
                    @else
                        <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Terminated</span>
                    @endif
                </div>
                <div><div class="f-label">Employment type</div>{{ \App\Models\User::EMPLOYMENT_TYPES[$employee->employment_type] ?? '—' }}</div>
                <div><div class="f-label">Date of joining</div>{{ optional($employee->date_of_joining)->format('j M Y') ?? '—' }}</div>
                <div><div class="f-label">Shift</div>{{ $employee->shift->name ?? '—' }}@if ($employee->workingHoursLabel()) <span style="color:var(--ink-soft);font-weight:400;">({{ $employee->workingHoursLabel() }})</span>@endif</div>
                <div><div class="f-label">Reports to</div>{{ $employee->reportingManager->name ?? '—' }}</div>
                @if ($canViewSalary)
                    <div>
                        <div class="f-label">Basic salary</div>
                        {{ $employee->basic_salary !== null ? number_format($employee->basic_salary, 2) : '—' }}
                        <a href="{{ route('admin.hr.employees.salary.edit', $employee) }}" style="margin-left:8px;font-size:12px;color:var(--primary-dark);">Manage →</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Account</h3></div>
            <div style="padding:16px 20px;">
                <p class="f-hint" style="margin:0 0 12px;">Resetting generates a new temporary password — the employee's current one stops working immediately.</p>
                <form method="POST" action="{{ route('admin.hr.employees.reset-password', $employee) }}" onsubmit="return confirm('Reset {{ $employee->name }}\'s password?');">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Reset Password</button>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
    .f-label{font-size:11px;font-weight:700;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.02em;margin-bottom:4px;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:0;}
</style>
@endsection
