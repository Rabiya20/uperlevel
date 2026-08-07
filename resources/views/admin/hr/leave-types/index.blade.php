@extends('layouts.admin')

@section('title', 'Leave Types — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Leave Types</h2>
        <p><a href="{{ route('admin.hr.settings') }}" style="color:var(--primary-dark);">← Back to HR Setup</a></p>
    </div>
    <a href="{{ route('admin.hr.leave-types.create') }}" class="btn btn-primary">+ Add Leave Type</a>
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
    <div class="panel-head"><h3>All leave types ({{ $leaveTypes->count() }})</h3></div>
    @if ($leaveTypes->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leave types yet — add one (e.g. "Annual", "Sick") to start tracking balances.</div>
    @else
        <table>
            <tr><th>Name</th><th>Days / Year</th><th>Carry Forward</th><th>Status</th><th></th></tr>
            @foreach ($leaveTypes as $type)
                <tr>
                    <td><strong>{{ $type->name }}</strong></td>
                    <td>{{ $type->days_per_year }}</td>
                    <td>{{ $type->carry_forward ? 'Yes' : 'No' }}</td>
                    <td>
                        @if ($type->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.hr.leave-types.edit', $type) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.hr.leave-types.destroy', $type) }}" onsubmit="return confirm('Delete this leave type?');">
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
