@extends('layouts.admin')

@section('title', 'Leave Management — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Leave Management</h2>
        <p>Balances and requests for {{ $year }}.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.hr.reports.index') }}" class="btn btn-ghost">Reports</a>
        <a href="{{ route('admin.hr.settings') }}" class="btn btn-ghost">HR Setup</a>
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

<div class="panel" style="margin-bottom:18px;">
    <div class="panel-head"><h3>Balances — {{ $year }}</h3></div>
    @if ($leaveTypes->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
            No leave types configured yet — add one from <a href="{{ route('admin.hr.leave-types.index') }}" style="color:var(--primary-dark);">HR Setup</a>.
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <tr>
                    <th>Employee</th>
                    @foreach ($leaveTypes as $type)
                        <th>{{ $type->name }}</th>
                    @endforeach
                    <th></th>
                </tr>
                @foreach ($balances as $row)
                    <tr>
                        <td><strong>{{ $row['user']->name }}</strong></td>
                        @foreach ($row['types'] as $entry)
                            <td>{{ $entry['balance']['remaining'] }} / {{ $entry['balance']['allowance'] }}</td>
                        @endforeach
                        <td><a href="{{ route('admin.hr.leaves.show', $row['user']) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">History</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
        <p class="f-hint" style="padding:14px 20px 16px;margin:0;">Shown as remaining / total (allowance plus any carried-forward days) for the year.</p>
    @endif
</div>

<div class="panel">
    <div class="panel-head">
        <h3>Requests {{ $pendingCount > 0 ? '('.$pendingCount.' pending)' : '' }}</h3>
    </div>
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status" onchange="this.form.submit()">
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Declined', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-ghost">Filter</button></noscript>
    </form>

    @if ($requests->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No {{ $status === 'all' ? '' : $status }} requests.</div>
    @else
        <table>
            <tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th></th></tr>
            @foreach ($requests as $leave)
                <tr>
                    <td>
                        <strong>{{ $leave->user->name }}</strong>
                        @if ($leave->reason)
                            <div style="font-size:11px;color:var(--ink-soft);">{{ $leave->reason }}</div>
                        @endif
                    </td>
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

@if ($requests->hasPages())
    <div style="margin-top:16px;">{{ $requests->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:0;}
</style>
@endsection
