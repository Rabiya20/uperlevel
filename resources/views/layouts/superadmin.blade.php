@extends('layouts.base')

@section('body-class', 'theme-superadmin')

@section('content')
<div class="app">
    <aside class="sidebar">
        <div class="brand-block">
            <div class="brand">
                <div class="company-mark" style="background:linear-gradient(135deg,var(--primary),var(--accent-2));">UL</div>
                <div>
                    <div class="brand-name">UperLevel</div>
                    <div class="brand-sub">Simplifying Operations</div>
                </div>
            </div>
        </div>
        <div class="platform-tag">Super Admin Console</div>

        <div class="nav-group-label">Platform</div>
        <x-module-nav :modules="$modules" type="sidebar" />

        <div class="sidebar-foot">
            <div class="mini-status">
                <span class="dot"></span>
                <span class="txt">All systems operational</span>
            </div>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>@yield('page-title', 'Platform Overview')</h1>
                <div class="topbar-sub">{{ now()->format('l, j F Y') }}</div>
            </div>
            <div class="top-actions">
                <div class="icon-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                </div>
                <div class="profile-chip" style="cursor:default;">
                    <div class="avatar-img" style="display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;background:linear-gradient(135deg,var(--primary),var(--accent-2));">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div>
                        <div class="name">{{ auth()->user()->name }}</div>
                        <div class="role">Super Admin</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="padding:8px 14px;">Logout</button>
                </form>
            </div>
        </div>

        <div class="content">
            @yield('content-body')
        </div>
    </main>
</div>
@endsection
