@extends('layouts.employee')

@section('title', 'My Attendance — UperLevel')
@section('page-title', 'My Attendance')

@section('content-body')
@php
    $calendarRoute = 'employee.attendance.index';
    $calendarRouteParams = [];
@endphp

<div class="page-head">
    <div>
        <h2>My Attendance</h2>
        <p>{{ $start->format('F Y') }} — check-ins, working hours and status at a glance.</p>
    </div>
</div>

@include('hr._attendance-stats')
@include('hr._attendance-calendar')
@endsection
