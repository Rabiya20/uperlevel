@extends('layouts.employee')

@section('title', 'Edit Lead — UperLevel')
@section('page-title', 'Edit Lead')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit Lead</h2>
        <p><a href="{{ route('employee.leads.show', $lead) }}" style="color:var(--primary-dark);">← Back to Lead</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('employee.leads.update', $lead) }}">
    @csrf
    @method('PUT')
    @include('crm._lead-form', ['lead' => $lead, 'settings' => $settings, 'isAdmin' => false])
</form>
@endsection
