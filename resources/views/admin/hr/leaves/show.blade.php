@extends('layouts.admin')

@section('title', $user->name.' — Leave History — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $user->name }}'s Leave</h2>
        <p><a href="{{ route('admin.hr.leaves.index') }}" style="color:var(--primary-dark);">← Back to Leave Management</a></p>
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

<div class="kpi-grid" style="margin-bottom:18px;">
    @foreach ($balances as $entry)
        <div class="kpi-card">
            <div class="kpi-label">{{ $entry['type']->name }} Remaining</div>
            <div class="kpi-value">{{ $entry['balance']['remaining'] }}</div>
            <div style="font-size:11px;color:var(--ink-soft);margin-top:2px;">of {{ $entry['balance']['allowance'] }} ({{ $entry['balance']['used'] }} used)</div>
        </div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-head"><h3>Request history — {{ $year }}</h3></div>
    @if ($history->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leave requests yet.</div>
    @else
        <table>
            <tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th></th></tr>
            @foreach ($history as $leave)
                <tr>
                    <td>{{ $leave->leaveType->name }}</td>
                    <td>{{ $leave->start_date->format('j M Y') }} – {{ $leave->end_date->format('j M Y') }}</td>
                    <td>{{ $leave->days }}</td>
                    <td>
                        @if ($leave->status === 'pending')
                            <span class="badge-pill" style="background:var(--primary-soft);color:var(--primary-dark);">Pending</span>
                        @elseif ($leave->status === 'approved')
                            <span class="badge-pill pill-active">Approved</span>
                        @else
                            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Declined</span>
                        @endif
                    </td>
                    <td>
                        @if ($leave->status === 'pending')
                            <div style="display:flex;gap:8px;">
                                <form method="POST" action="{{ route('admin.hr.leaves.approve', $leave) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="padding:6px 12px;font-size:12px;">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.hr.leaves.reject', $leave) }}" onsubmit="return confirm('Decline this request?');">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Decline</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
