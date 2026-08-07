@extends('layouts.base')

@section('body-class', 'theme-admin')

@php
    $tenant = $currentTenant ?? null;
    $todayAttendance = $tenant && !auth()->user()->isSuperAdmin()
        ? \App\Models\Attendance::where('user_id', auth()->id())->where('work_date', now()->toDateString())->first()
        : null;
    $teamPresentLabel = $tenant ? ($todayAttendanceCount ?? 0).' of '.($teamSize ?? $tenant->users()->count()).' team members checked in' : '';
@endphp

@section('content')
@include('partials.impersonation-banner')

<header class="header-1">
    <div class="brand-cluster">
        <div class="company-logo" style="display:flex;align-items:center;gap:9px;">
            <div class="company-mark">{{ $tenant->logo_initials ?? 'CO' }}</div>
            <div>
                <div class="company-name">{{ $tenant->name ?? 'Company' }}</div>
                <div class="company-sub">Owner Workspace</div>
            </div>
        </div>
        <div class="divider-v"></div>
        <div class="powered">
            <div class="tf-mark">UL</div>
            <div class="powered-text">Powered by<br><b>UperLevel</b></div>
        </div>
    </div>

    <x-module-nav :modules="$modules" type="header" />

    <div class="top-actions" style="display:flex;align-items:center;gap:14px;">
        @include('partials.notifications-dropdown')
        @include('partials.profile-dropdown')
    </div>
</header>

@include('partials.admin-subnav')

<div class="content">
    @if (request()->routeIs('admin.dashboard'))
        @include('partials.checkin-banner', ['showCheckoutButton' => false])
    @endif

    @yield('content-body')
</div>
@endsection
