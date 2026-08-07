@extends('layouts.admin')

@section('title', 'Add Role — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Role</h2>
        <p><a href="{{ route('admin.company.roles.index') }}" style="color:var(--primary-dark);">← Back to User Roles</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.company.roles.store') }}">
    @csrf
    @include('admin.company.roles._form', [
        'role' => null, 'adminModules' => $adminModules, 'employeeModules' => $employeeModules, 'permissions' => $permissions,
    ])
</form>
@endsection
