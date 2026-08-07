@extends('layouts.superadmin')

@section('title', 'Platform Dashboard — UperLevel')
@section('page-title', 'Platform Overview')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Welcome back, {{ auth()->user()->name }} 👋</h2>
        <p>Here's what's happening across all UperLevel tenants today.</p>
    </div>
    <a href="{{ route('superadmin.companies.index') }}" class="btn btn-primary">View All Companies</a>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Active Tenants</div>
        <div class="kpi-value">{{ $stats['active_tenants'] }}</div>
        <div class="kpi-delta up">Out of {{ $stats['total_tenants'] }} total</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Trial Tenants</div>
        <div class="kpi-value">{{ $stats['trial_tenants'] }}</div>
        <div class="kpi-delta">Being onboarded</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Companies</div>
        <div class="kpi-value">{{ $stats['total_tenants'] }}</div>
        <div class="kpi-delta">On the platform</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Support Tickets</div>
        <div class="kpi-value">0</div>
        <div class="kpi-delta">Coming soon</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h3>Recently Added Tenants</h3>
        <a href="{{ route('superadmin.companies.index') }}" class="link">View all →</a>
    </div>
    <table>
        <tr><th>Company</th><th>Plan</th><th>Users</th><th>Status</th><th></th></tr>
        @foreach ($recentTenants as $tenant)
            <tr>
                <td><strong>{{ $tenant->name }}</strong></td>
                <td>{{ ucfirst($tenant->plan) }}</td>
                <td>{{ $tenant->users_count }}</td>
                <td>
                    <span class="badge-pill pill-{{ $tenant->status === 'active' ? 'active' : ($tenant->status === 'trial' ? 'trial' : 'suspended') }}">
                        <span class="mini-dot"></span>{{ ucfirst($tenant->status) }}
                    </span>
                </td>
                <td>
                    <form method="POST" action="{{ route('superadmin.companies.enter', $tenant) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Login as →</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
