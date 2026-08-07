@extends('layouts.admin')

@section('title', 'Attendance — UperLevel')

@section('content-body')
@php
    $isPastDate = $date->copy()->startOfDay()->lessThan(now()->startOfDay());
@endphp

<div class="page-head">
    <div>
        <h2>Attendance</h2>
        <p>{{ $date->format('l, j F Y') }} — every team member's check-in status for the day.</p>
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

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Date</label>
            <input class="f-input" type="date" name="date" value="{{ $date->format('Y-m-d') }}">
        </div>
        <div>
            <label class="f-label">Team member</label>
            <select class="f-input" name="user_id">
                <option value="">Everyone</option>
                @foreach ($allUsers as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
        @if (request()->anyFilled(['date', 'user_id']))
            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-ghost" style="padding:9px 16px;">Today, everyone</a>
        @endif
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $users->total() }} team member{{ $users->total() === 1 ? '' : 's' }}</h3></div>
    <table>
        <tr><th>Name</th><th>Shift</th><th>Check In</th><th>Check Out</th><th>Worked</th><th>Status</th><th></th></tr>
        @foreach ($users as $user)
            @php $att = $attendanceByUser->get($user->id); @endphp
            <tr>
                <td>
                    <strong>{{ $user->name }}</strong>
                    <div style="font-size:11px;color:var(--ink-soft);text-transform:capitalize;">{{ $user->role }}</div>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.hr.attendance.assign-shift', $user) }}">
                        @csrf
                        <select class="f-input f-input-inline" name="shift_id" onchange="this.form.submit()">
                            <option value="">No shift</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected($user->shift_id === $shift->id)>{{ $shift->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </td>
                <td>{{ $att && $att->check_in ? $currentTenant->localizeTime($att->check_in)->format('g:i A') : '—' }}</td>
                <td>{{ $att && $att->check_out ? $currentTenant->localizeTime($att->check_out)->format('g:i A') : '—' }}</td>
                <td>
                    @php $minutes = $att?->workedMinutes(); @endphp
                    {{ $minutes !== null ? number_format($minutes / 60, 1).'h' : '—' }}
                </td>
                <td>
                    @php $onLeave = $onLeaveByUser->get($user->id); @endphp
                    @if ($att && $att->check_in)
                        @if ($att->isLate())
                            <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">Late</span>
                        @else
                            <span class="badge-pill pill-active">Present</span>
                        @endif
                    @elseif ($holidayToday)
                        <span class="badge-pill" style="background:#E6F4F8;color:#0E7C9C;">{{ $holidayToday->name }}</span>
                    @elseif ($onLeave)
                        <span class="badge-pill" style="background:#EEE9FB;color:#6C4FD6;">On Leave — {{ $onLeave->leaveType->name }}</span>
                    @elseif ($att && $att->status === 'absent')
                        <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Absent</span>
                    @elseif ($isPastDate)
                        <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Absent</span>
                    @else
                        <span class="badge-pill" style="background:var(--primary-soft);color:var(--primary-dark);">Not checked in</span>
                    @endif
                </td>
                <td><a href="{{ route('admin.hr.attendance.show', $user) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">History</a></td>
            </tr>
        @endforeach
    </table>
</div>

@if ($users->hasPages())
    <div style="margin-top:16px;">{{ $users->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input-inline{padding:6px 8px;font-size:12.5px;min-width:130px;}
</style>
@endsection
