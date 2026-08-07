@extends('layouts.admin')

@section('title', 'Shifts — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Shifts</h2>
        <p><a href="{{ route('admin.hr.settings') }}" style="color:var(--primary-dark);">← Back to HR Setup</a></p>
    </div>
    <a href="{{ route('admin.hr.shifts.create') }}" class="btn btn-primary">+ Add Shift</a>
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

<div class="panel">
    <div class="panel-head"><h3>All shifts ({{ $shifts->count() }})</h3></div>
    @if ($shifts->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No shifts yet — add one to start assigning employees.</div>
    @else
        <table>
            <tr><th>Name</th><th>Hours</th><th>Grace</th><th>Break</th><th>Employees</th><th>Status</th><th></th></tr>
            @foreach ($shifts as $shift)
                <tr>
                    <td><strong>{{ $shift->name }}</strong> @if ($shift->is_default) <span class="badge-pill" style="background:var(--primary-soft);color:var(--primary-dark);">Default</span> @endif</td>
                    <td>{{ $shift->formattedRange() }}</td>
                    <td>{{ $shift->grace_minutes }} min</td>
                    <td>{{ $shift->break_minutes }} min</td>
                    <td>{{ $shift->employees_count }}</td>
                    <td>
                        @if ($shift->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.hr.shifts.edit', $shift) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.hr.shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
