@extends('layouts.employee')

@section('title', $lead->name.' — My Leads — UperLevel')
@section('page-title', $lead->name)

@section('content-body')
@php $routePrefix = 'employee.leads'; @endphp

<div class="page-head">
    <div>
        <h2>{{ $lead->name }}</h2>
        <p><a href="{{ route('employee.leads.index') }}" style="color:var(--primary-dark);">← Back to My Leads</a></p>
    </div>
    @if ($lead->canEdit(auth()->user()))
        <a href="{{ route('employee.leads.edit', $lead) }}" class="btn btn-ghost">Edit</a>
    @endif
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

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Lead Details</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><span style="color:var(--ink-soft);">Company</span><div style="font-weight:600;">{{ $lead->company_name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Source</span><div style="font-weight:600;">{{ $lead->source ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Email</span><div style="font-weight:600;">{{ $lead->email ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Phone</span><div style="font-weight:600;">{{ $lead->phone ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Country</span><div style="font-weight:600;">{{ $lead->country ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Budget</span><div style="font-weight:600;">{{ $lead->budget !== null ? $lead->currency.' '.number_format((float) $lead->budget, 2) : '—' }}</div></div>
                @if ($lead->description)
                    <div style="grid-column:1 / -1;"><span style="color:var(--ink-soft);">Description</span><div>{{ $lead->description }}</div></div>
                @endif
            </div>
        </div>

        @include('crm._lead-status', ['lead' => $lead, 'settings' => $settings, 'routePrefix' => $routePrefix, 'isAdmin' => false])
        @include('crm._lead-followups', ['lead' => $lead, 'routePrefix' => $routePrefix])

    </div>

    <div>
        @include('crm._lead-activity', ['lead' => $lead, 'routePrefix' => $routePrefix])
    </div>
</div>
@endsection
