@extends('layouts.admin')

@section('title', 'Special Holidays — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Special Holidays</h2>
        <p>Company-wide holidays — shown on every employee's attendance calendar and excluded from absence tracking.</p>
    </div>
    <a href="{{ route('admin.company.holidays.create') }}" class="btn btn-primary">+ Add Holiday</a>
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
    <div class="panel-head"><h3>All holidays ({{ $holidays->total() }})</h3></div>
    @if ($holidays->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No holidays added yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Date</th><th>Day</th><th></th></tr>
            @foreach ($holidays as $holiday)
                <tr>
                    <td><strong>{{ $holiday->name }}</strong></td>
                    <td>{{ $holiday->date->format('j M Y') }}</td>
                    <td>{{ $holiday->date->format('l') }}</td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.company.holidays.edit', $holiday) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.company.holidays.destroy', $holiday) }}" onsubmit="return confirm('Delete this holiday?');">
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

@if ($holidays->hasPages())
    <div style="margin-top:16px;">{{ $holidays->links() }}</div>
@endif
@endsection
