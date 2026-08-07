@extends('layouts.admin')

@section('title', 'Add Shift — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Shift</h2>
        <p><a href="{{ route('admin.hr.shifts.index') }}" style="color:var(--primary-dark);">← Back to Shifts</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.hr.shifts.store') }}">
    @csrf
    @include('admin.hr.shifts._form', ['shift' => null])
</form>
@endsection
