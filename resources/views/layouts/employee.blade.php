@extends('layouts.base')

@section('body-class', 'theme-employee')

@php
    $tenant = $currentTenant ?? auth()->user()->tenant;
    $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())->where('work_date', now()->toDateString())->first();
@endphp

@section('content')
<div class="app">
    <aside class="sidebar">
        <div class="brand-block">
            <div class="company-row">
                <div class="company-mark">{{ $tenant->logo_initials ?? 'CO' }}</div>
                <div>
                    <div class="company-name">{{ $tenant->name ?? 'Company' }}</div>
                    <div class="company-sub">Employee Portal</div>
                </div>
            </div>
            <div class="powered-strip">
                <div class="tf-mark">UL</div>
                <div class="txt">Powered by UperLevel<br>Simplifying Operations</div>
            </div>
        </div>

        <div class="nav-group-label">My Work</div>
        <x-module-nav :modules="$modules" type="sidebar" />

        <div class="sidebar-foot">
            <div class="mini-status">
                <span class="dot"></span>
                <span class="txt">
                    @if ($todayAttendance)
                        Checked in · {{ $tenant->localizeTime($todayAttendance->check_in)->format('g:i A') }}
                    @else
                        Not checked in
                    @endif
                </span>
            </div>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>@yield('page-title', 'My Dashboard')</h1>
                <div class="topbar-sub">{{ $tenant->localNow()->format('l, j F Y') }}</div>
            </div>
            <div class="top-actions">
                <div class="icon-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                </div>
                @include('partials.profile-dropdown')
            </div>
        </div>

        <div class="content">
            @if (request()->routeIs('employee.dashboard'))
                @include('partials.screen-monitoring-notice')
                @include('partials.checkin-banner', ['showCheckoutButton' => true])
            @endif

            @yield('content-body')
        </div>
    </main>
</div>
@endsection
