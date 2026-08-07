@extends('layouts.employee')

@section('title', 'My Dashboard — UperLevel')
@section('page-title', 'My Dashboard')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Good day, {{ auth()->user()->name }} 👋</h2>
        <p>Here's what's on your plate today.</p>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Status Today</div>
        <div class="kpi-value" style="font-size:18px;">
            @if ($today && $today->check_out)
                Checked out
            @elseif ($today)
                Checked in
            @else
                Not started
            @endif
            @if ($today && $today->isLate() !== null)
                @if ($today->isLate())
                    <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;font-size:10px;vertical-align:middle;margin-left:6px;">Late</span>
                @else
                    <span class="badge-pill pill-active" style="font-size:10px;vertical-align:middle;margin-left:6px;">On time</span>
                @endif
            @endif
        </div>
        <div class="kpi-delta">
            @if ($today)
                In at {{ $currentTenant->localizeTime($today->check_in)->format('g:i A') }}
                @if ($today->check_out)
                    &middot; Out at {{ $currentTenant->localizeTime($today->check_out)->format('g:i A') }}
                @endif
                @php $workedMinutes = $today->workedMinutes(); @endphp
                @if ($workedMinutes !== null)
                    &middot; Worked {{ number_format($workedMinutes / 60, 1) }}h
                @endif
            @endif
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">My Tasks</div>
        <div class="kpi-value">—</div>
        <div class="kpi-delta">Project module coming soon</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Leave Balance</div>
        <div class="kpi-value">—</div>
        <div class="kpi-delta">HR module coming soon</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Unread Notices</div>
        <div class="kpi-value">—</div>
        <div class="kpi-delta">Notice board coming soon</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h3>Getting started</h3></div>
    <div class="activity-item">
        <span class="activity-dot" style="background:var(--primary);"></span>
        <div class="activity-text">This is your Employee Portal home. Attendance, tasks, leaves and the other modules in your sidebar will be built out next.</div>
    </div>
</div>
@endsection
