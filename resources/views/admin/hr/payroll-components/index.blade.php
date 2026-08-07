@extends('layouts.admin')

@section('title', 'Payroll Structure — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Payroll Structure</h2>
        <p><a href="{{ route('admin.hr.settings') }}" style="color:var(--primary-dark);">← Back to HR Setup</a></p>
    </div>
    <a href="{{ route('admin.hr.payroll-components.create') }}" class="btn btn-primary">+ Add Component</a>
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
    <div class="panel-head"><h3>Pay Components ({{ $components->count() }})</h3></div>
    @if ($components->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
            No components yet — add ones like "Basic" (payable) or "Tax" (deductible) to build each employee's payroll structure from.
        </div>
    @else
        <table>
            <tr><th>Name</th><th>Type</th><th>Status</th><th></th></tr>
            @foreach ($components as $component)
                <tr>
                    <td><strong>{{ $component->name }}</strong></td>
                    <td>
                        @if ($component->type === 'earning')
                            <span class="badge-pill pill-active">Payable</span>
                        @else
                            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;">Deductible</span>
                        @endif
                    </td>
                    <td>
                        @if ($component->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.hr.payroll-components.edit', $component) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.hr.payroll-components.destroy', $component) }}" onsubmit="return confirm('Delete this component?');">
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
