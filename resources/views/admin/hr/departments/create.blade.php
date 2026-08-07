@extends('layouts.admin')

@section('title', 'Add Department — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Department</h2>
        <p><a href="{{ route('admin.hr.departments.index') }}" style="color:var(--primary-dark);">← Back to Departments</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.hr.departments.store') }}">
    @csrf
    @include('admin.hr.departments._form', ['department' => null])
</form>
@endsection
