@extends('layouts.employee')

@section('title', $batch->original_filename.' — Import History — UperLevel')
@section('page-title', 'Import Detail')

@section('content-body')
@php
    $rowStatusColors = [
        'valid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50', 'label' => 'Valid'],
        'duplicate' => ['bg' => '#FFF4E5', 'fg' => '#B4690E', 'label' => 'Duplicate'],
        'error' => ['bg' => '#FDEEEC', 'fg' => '#C0392B', 'label' => 'Error'],
    ];
    $statusColors = [
        'pending_review' => ['bg' => '#FFF4E5', 'fg' => '#B4690E', 'label' => 'Pending Review'],
        'completed' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50', 'label' => 'Approved & Imported'],
        'rejected' => ['bg' => '#FDEEEC', 'fg' => '#C0392B', 'label' => 'Rejected'],
    ];
    $sc = $statusColors[$batch->status] ?? ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)', 'label' => ucfirst($batch->status)];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $batch->original_filename }}</h2>
        <p><a href="{{ route('employee.leads.imports.index') }}" style="color:var(--primary-dark);">← Back to Import History</a></p>
    </div>
    <span class="badge-pill" style="background:{{ $sc['bg'] }};color:{{ $sc['fg'] }};">{{ $sc['label'] }}</span>
</div>

@if ($batch->status === 'pending_review')
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FFF4E5;border-color:#FFF4E5;">
        <span style="color:#B4690E;font-weight:600;font-size:13px;">Waiting on an admin to review this before these become real leads.</span>
    </div>
@endif

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Total Rows</div><div class="kpi-value">{{ $batch->total_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Valid</div><div class="kpi-value">{{ $batch->valid_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Duplicates</div><div class="kpi-value">{{ $batch->duplicate_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Errors</div><div class="kpi-value">{{ $batch->error_rows }}</div></div>
</div>

<div class="panel">
    <div class="panel-head"><h3>Rows</h3></div>
    <table>
        <tr><th>#</th><th>Name</th><th>Company</th><th>Email</th><th>Status</th><th>Detail</th></tr>
        @foreach ($batch->rows as $row)
            @php $rc = $rowStatusColors[$row->status]; @endphp
            <tr>
                <td>{{ $row->row_number }}</td>
                <td>{{ $row->name ?? '—' }}</td>
                <td>{{ $row->company_name ?? '—' }}</td>
                <td>{{ $row->email ?? '—' }}</td>
                <td><span class="badge-pill" style="background:{{ $rc['bg'] }};color:{{ $rc['fg'] }};">{{ $rc['label'] }}</span></td>
                <td style="font-size:12px;color:var(--ink-soft);">
                    @if ($row->status === 'error')
                        {{ $row->error_message }}
                    @elseif ($row->imported_lead_id)
                        Imported
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
