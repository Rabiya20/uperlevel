@extends('layouts.admin')

@section('title', 'Add Leave Type — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Leave Type</h2>
        <p><a href="{{ route('admin.hr.leave-types.index') }}" style="color:var(--primary-dark);">← Back to Leave Types</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.hr.leave-types.store') }}">
    @csrf
    @include('admin.hr.leave-types._form', ['leaveType' => null, 'existingTypes' => $existingTypes])
</form>
@endsection
