@extends('layouts.admin')

@section('title', 'Edit Designation — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit Designation</h2>
        <p><a href="{{ route('admin.hr.designations.index') }}" style="color:var(--primary-dark);">← Back to Designations</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.hr.designations.update', $designation) }}">
    @csrf
    @method('PUT')
    @include('admin.hr.designations._form', ['designation' => $designation, 'departments' => $departments])
</form>
@endsection
