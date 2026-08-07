@extends('layouts.admin')

@section('title', $user->name.' — Attendance — UperLevel')

@section('content-body')
@php
    $calendarRoute = 'admin.hr.attendance.show';
    $calendarRouteParams = ['user' => $user->id];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $user->name }}</h2>
        <p><a href="{{ route('admin.hr.attendance.index') }}" style="color:var(--primary-dark);">← Back to Attendance</a> — {{ $start->format('F Y') }}, {{ $user->shift->name ?? 'no shift assigned' }}</p>
    </div>
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

@include('hr._attendance-stats')
@include('hr._attendance-calendar')
@endsection
