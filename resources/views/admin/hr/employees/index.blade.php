@extends('layouts.admin')

@section('title', 'Employees — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Employees</h2>
        <p>{{ $employeeTotal }} team member{{ $employeeTotal === 1 ? '' : 's' }} on record.</p>
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
            <input id="employee-search" class="f-input" type="text" name="search" value="{{ request('search') }}" placeholder="Name, email or code">
        </div>
        <div>
            <label class="f-label">Department</label>
                <select id="employee-department" class="f-input" name="department">
                <option value="">All</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((int) request('department') === $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Status</label>
                <select id="employee-status" class="f-input" name="status">
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
        <table id="employees-table">
            <thead>
                <tr><th>Photo</th><th>Name</th><th>Employee Code</th><th>Role</th><th>Department</th><th>Shift</th><th>Status</th><th data-sortable="false"></th></tr>
            </thead>
            <tbody><tr><td colspan="8" style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">Loading employees...</td></tr></tbody>
        </table>
        <div id="employees-table-pagination" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-top:1px solid var(--line);"></div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var filters = document.querySelector('form');
        var table = dataTable('employees-table', {
            endpoint: @json(route('admin.hr.employees.index')),
            paginationContainer: '#employees-table-pagination',
            searchInput: '#employee-search',
            pageSize: 10,
            pageSizeOptions: [10, 25, 50],
            params: function () {
                return {
                    department: document.getElementById('employee-department').value,
                    status: document.getElementById('employee-status').value
                };
            },
            rowRenderer: function (employee) {
                var status = employee.employment_status === 'active'
                    ? '<span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>'
                    : employee.employment_status === 'on_leave'
                        ? '<span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">On Leave</span>'
                        : '<span class="badge-pill pill-suspended"><span class="mini-dot"></span>Terminated</span>';
                return '<tr><td><img src="' + dataTable.escape(employee.avatar) + '" alt="' + dataTable.escape(employee.name) + '" style="width:34px;height:34px;border-radius:50%;object-fit:cover;background:#DCE6F7;display:block;"></td>'
                    + '<td><strong>' + dataTable.escape(employee.name) + '</strong><div style="font-size:11px;color:var(--ink-soft);">' + dataTable.escape(employee.email) + '</div></td>'
                    + '<td>' + dataTable.escape(employee.employee_code || '—') + '</td><td style="text-transform:capitalize;">' + dataTable.escape(employee.role) + '</td>'
                    + '<td>' + dataTable.escape(employee.department || '—') + '</td><td>' + dataTable.escape(employee.shift || '—') + '</td><td>' + status + '</td>'
                    + '<td><div style="display:flex;gap:8px;"><a href="' + dataTable.escape(employee.show_url) + '" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a><a href="' + dataTable.escape(employee.edit_url) + '" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a><form method="POST" action="' + dataTable.escape(employee.delete_url) + '" onsubmit="return confirm(\'Remove this employee from the directory?\');"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#C0392B;">Delete</button></form></div></td></tr>';
            }
        });
        filters.addEventListener('submit', function (event) { event.preventDefault(); table.reload(); });
    });
</script>
@endpush
@endsection
