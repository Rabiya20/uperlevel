@extends('layouts.admin')

@section('title', 'Log History — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Log History</h2>
        <p>An automatic, system-wide audit trail — every meaningful create, update or delete across the app, who did it, and when.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Description contains…">
        </div>
        <div>
            <label class="f-label">User</label>
            <select class="f-input" name="user_id">
                <option value="">Everyone</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Action</label>
            <select class="f-input" name="action">
                <option value="">Any</option>
                @foreach ($actions as $a)
                    <option value="{{ $a }}" @selected(request('action') === $a)>{{ ucwords(str_replace('_', ' ', $a)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">From</label>
            <input class="f-input" type="date" name="from" value="{{ request('from') }}">
        </div>
        <div>
            <label class="f-label">To</label>
            <input class="f-input" type="date" name="to" value="{{ request('to') }}">
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
        @if (request()->anyFilled(['q', 'user_id', 'action', 'from', 'to']))
            <a href="{{ route('admin.company.logs.index') }}" class="btn btn-ghost" style="padding:9px 16px;">Clear</a>
        @endif
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $logs->total() }} log {{ $logs->total() === 1 ? 'entry' : 'entries' }}</h3></div>
    @if ($logs->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No activity recorded yet.</div>
    @else
        <table>
            <tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr>
            @foreach ($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $currentTenant->localizeTime($log->created_at)->format('j M Y, g:i A') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>
                        @php
                            $colors = [
                                'created' => ['#E9F7EF', '#0F7C50'],
                                'updated' => ['#FFF4E5', '#B4690E'],
                                'deleted' => ['#FDEEEC', '#C0392B'],
                            ];
                            [$bg, $fg] = $colors[$log->action] ?? ['var(--primary-soft)', 'var(--primary-dark)'];
                        @endphp
                        <span class="badge-pill" style="background:{{ $bg }};color:{{ $fg }};">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span>
                    </td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($logs->hasPages())
    <div style="margin-top:16px;">{{ $logs->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
