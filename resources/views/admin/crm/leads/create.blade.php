@extends('layouts.admin')

@section('title', 'Add Lead — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Lead</h2>
        <p><a href="{{ route('admin.crm.leads.index') }}" style="color:var(--primary-dark);">← Back to Leads</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.crm.leads.store') }}">
    @csrf
    @include('crm._lead-form', ['lead' => null, 'settings' => $settings, 'employees' => $employees, 'isAdmin' => true])
</form>
@endsection
