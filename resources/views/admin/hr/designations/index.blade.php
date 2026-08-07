@extends('layouts.admin')

@section('title', 'Designations — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Designations</h2>
        <p><a href="{{ route('admin.hr.settings') }}" style="color:var(--primary-dark);">← Back to HR Setup</a></p>
    </div>
    <a href="{{ route('admin.hr.designations.create') }}" class="btn btn-primary">+ Add Designation</a>
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
    <div class="panel-head"><h3>All designations ({{ $designations->count() }})</h3></div>
    @if ($designations->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No designations yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Department</th><th>Employees</th><th>Status</th><th></th></tr>
            @foreach ($designations as $designation)
                <tr>
                    <td><strong>{{ $designation->name }}</strong></td>
                    <td>{{ $designation->department->name ?? '—' }}</td>
                    <td>{{ $designation->users_count }}</td>
                    <td>
                        @if ($designation->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.hr.designations.edit', $designation) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.hr.designations.destroy', $designation) }}" onsubmit="return confirm('Delete this designation?');">
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
