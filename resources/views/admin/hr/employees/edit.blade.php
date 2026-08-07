@extends('layouts.admin')

@section('title', 'Edit '.$employee->name.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit {{ $employee->name }}</h2>
        <p><a href="{{ route('admin.hr.employees.show', $employee) }}" style="color:var(--primary-dark);">← Back to Profile</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}">
    @csrf
    @method('PUT')
    @include('admin.hr.employees._form', ['employee' => $employee, 'managers' => $managers, 'shifts' => $shifts, 'departments' => $departments, 'designations' => $designations, 'roles' => $roles])
</form>
@endsection
