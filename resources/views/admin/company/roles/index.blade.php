@extends('layouts.admin')

@section('title', 'User Roles — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>User Roles</h2>
        <p>Custom roles for fine-grained module access, beyond the base Owner/Admin/Manager/Employee levels.</p>
    </div>
    <a href="{{ route('admin.company.roles.create') }}" class="btn btn-primary">+ Add Role</a>
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

<div class="panel">
    <div class="panel-head"><h3>All roles ({{ $roles->count() }})</h3></div>
    @if ($roles->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
            No custom roles yet — the base Owner/Admin/Manager/Employee levels are still in effect for everyone until you add one.
        </div>
    @else
        <table>
            <tr><th>Name</th><th>Portal</th><th>Users</th><th>Status</th><th></th></tr>
            @foreach ($roles as $role)
                <tr>
                    <td><strong>{{ $role->name }}</strong></td>
                    <td>{{ $role->portal === 'admin' ? 'Admin Portal' : 'Employee Portal' }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @if ($role->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.company.roles.edit', $role) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.company.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
