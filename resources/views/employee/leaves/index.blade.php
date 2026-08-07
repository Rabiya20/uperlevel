@extends('layouts.employee')

@section('title', 'My Leaves — UperLevel')
@section('page-title', 'My Leaves')

@section('content-body')
<div class="page-head">
    <div>
        <h2>My Leaves</h2>
        <p>{{ $year }} balances and requests.</p>
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
    @forelse ($balances as $entry)
        <div class="kpi-card">
            <div class="kpi-label">{{ $entry['type']->name }} Remaining</div>
            <div class="kpi-value">{{ $entry['balance']['remaining'] }}</div>
            <div style="font-size:11px;color:var(--ink-soft);margin-top:2px;">of {{ $entry['balance']['allowance'] }} ({{ $entry['balance']['used'] }} used)</div>
        </div>
    @empty
        <div style="color:var(--ink-soft);font-size:13.5px;">No leave types have been set up for your company yet.</div>
    @endforelse
</div>

<div class="grid-2" style="align-items:start;">
    <div class="panel">
        <div class="panel-head"><h3>Request Leave</h3></div>
        <form method="POST" action="{{ route('employee.leaves.store') }}" style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div>
                <label class="f-label">Leave type</label>
                <select class="f-input" name="leave_type_id" required>
                    <option value="">Select…</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="f-label">Start date</label>
                    <input class="f-input" type="date" name="start_date" value="{{ old('start_date') }}" required>
                </div>
                <div>
                    <label class="f-label">End date</label>
                    <input class="f-input" type="date" name="end_date" value="{{ old('end_date') }}" required>
                </div>
            </div>
            <div>
                <label class="f-label">Reason (optional)</label>
                <textarea class="f-input" name="reason" rows="3" maxlength="500">{{ old('reason') }}</textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-head"><h3>My Requests</h3></div>
        @if ($history->isEmpty())
            <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leave requests yet.</div>
        @else
            <table>
                <tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr>
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
                            @if ($leave->decision_note)
                                <div style="font-size:11px;color:var(--ink-soft);margin-top:4px;">{{ $leave->decision_note }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
</style>
@endsection
