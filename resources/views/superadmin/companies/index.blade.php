@extends('layouts.superadmin')

@section('title', 'Companies — UperLevel')
@section('page-title', 'Tenants')

@section('content-body')
<div class="page-head">
    <div>
        <h2>All companies</h2>
        <p>Every tenant on the platform. Enter any company's Admin Portal instantly for support — no extra login needed.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h3>Companies ({{ $tenants->count() }})</h3></div>
    <table>
        <tr><th>Company</th><th>Plan</th><th>Users</th><th>Status</th><th></th></tr>
        @foreach ($tenants as $tenant)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:#1B2A3A;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;">{{ $tenant->logo_initials }}</div>
                        <div>
                            <div style="font-weight:700;">{{ $tenant->name }}</div>
                            <div style="font-size:11px;color:var(--ink-soft);">{{ $tenant->slug }}.techflow.app</div>
                        </div>
                    </div>
                </td>
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
                        <button type="submit" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;">Login as company →</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
