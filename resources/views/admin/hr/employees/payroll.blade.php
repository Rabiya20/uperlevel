@extends('layouts.admin')

@section('title', $employee->name.' — Payroll Structure — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $employee->name }}'s Payroll Structure</h2>
        <p><a href="{{ route('admin.hr.employees.show', $employee) }}" style="color:var(--primary-dark);">← Back to Profile</a></p>
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
    <div class="kpi-card">
        <div class="kpi-label">Total Payable</div>
        <div class="kpi-value">{{ number_format($totals['payable'], 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Deductible</div>
        <div class="kpi-value">{{ number_format($totals['deductible'], 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Net</div>
        <div class="kpi-value">{{ number_format($totals['net'], 2) }}</div>
    </div>
</div>

@if ($components->isEmpty())
    <div class="panel">
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
            No payroll components configured yet — add some from <a href="{{ route('admin.hr.payroll-components.index') }}" style="color:var(--primary-dark);">HR Setup → Payroll Structure</a>.
        </div>
    </div>
@else
    <form method="POST" action="{{ route('admin.hr.employees.payroll.update', $employee) }}">
        @csrf
        @method('PUT')

        <div class="panel">
            <div class="panel-head"><h3>Components</h3></div>
            <table>
                <tr><th>Component</th><th>Type</th><th>Amount</th></tr>
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
                            <input class="f-input" style="max-width:180px;" type="number" step="0.01" min="0"
                                name="amounts[{{ $component->id }}]"
                                value="{{ old('amounts.'.$component->id, $assigned[$component->id] ?? '') }}"
                                placeholder="0.00">
                        </td>
                    </tr>
                @endforeach
            </table>
            <p class="f-hint" style="padding:14px 20px 16px;margin:0;">Leave a field blank to remove that component from this employee's structure.</p>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Save Payroll Structure</button>
        </div>
    </form>
@endif

<style>
    .f-input{padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:0;}
</style>
@endsection
