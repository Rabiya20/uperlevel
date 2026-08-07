@extends('layouts.admin')

@section('title', 'Add Employee — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Employee</h2>
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

<form method="POST" action="{{ route('admin.hr.employees.store') }}">
    @csrf
    @include('admin.hr.employees._form', ['employee' => null, 'managers' => $managers, 'shifts' => $shifts, 'departments' => $departments, 'designations' => $designations, 'suggestedCode' => $suggestedCode, 'roles' => $roles])
</form>
@endsection
