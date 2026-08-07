@extends('layouts.admin')

@section('title', 'Payroll — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Payroll</h2>
        <p>Current pay structure and net totals for every employee.</p>
    </div>
    <a href="{{ route('admin.hr.payroll-components.index') }}" class="btn btn-ghost">Payroll Components</a>
</div>

<div class="panel">
    @if ($employees->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No employees yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Department</th><th>Payable</th><th>Deductible</th><th>Net</th><th></th></tr>
            @foreach ($employees as $employee)
                @php $t = $totals[$employee->id]; @endphp
                <tr>
                    <td>
                        <strong>{{ $employee->name }}</strong>
                        <div style="font-size:11px;color:var(--ink-soft);text-transform:capitalize;">{{ $employee->role }}</div>
                    </td>
                    <td>{{ $employee->department->name ?? '—' }}</td>
                    <td>{{ number_format($t['payable'], 2) }}</td>
                    <td>{{ number_format($t['deductible'], 2) }}</td>
                    <td><strong>{{ number_format($t['net'], 2) }}</strong></td>
                    <td><a href="{{ route('admin.hr.employees.payroll.edit', $employee) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
