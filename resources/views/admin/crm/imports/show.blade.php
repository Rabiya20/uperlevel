@extends('layouts.admin')

@section('title', $batch->original_filename.' — Imports — UperLevel')

@section('content-body')
@php
    $rowStatusColors = [
        'valid' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50', 'label' => 'Valid'],
        'duplicate' => ['bg' => '#FFF4E5', 'fg' => '#B4690E', 'label' => 'Duplicate'],
        'error' => ['bg' => '#FDEEEC', 'fg' => '#C0392B', 'label' => 'Error'],
    ];
@endphp

<div class="page-head">
    <div>
        <h2>{{ $batch->original_filename }}</h2>
        <p><a href="{{ route('admin.crm.imports.index') }}" style="color:var(--primary-dark);">← Back to Imports</a></p>
    </div>
    @if ($batch->status === 'pending_review')
        <div style="display:flex;gap:10px;">
            <form method="POST" action="{{ route('admin.crm.imports.approve', $batch) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Approve & Import</button>
            </form>
            <form method="POST" action="{{ route('admin.crm.imports.reject', $batch) }}" onsubmit="return confirm('Reject this import? No leads will be created.');">
                @csrf
                <button type="submit" class="btn btn-ghost" style="color:#c0392b;">Reject</button>
            </form>
        </div>
    @endif
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Total Rows</div><div class="kpi-value">{{ $batch->total_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Valid</div><div class="kpi-value">{{ $batch->valid_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Duplicates</div><div class="kpi-value">{{ $batch->duplicate_rows }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Errors</div><div class="kpi-value">{{ $batch->error_rows }}</div></div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <div class="panel-head"><h3>Batch Details</h3></div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
        <div><span style="color:var(--ink-soft);">Uploaded by</span><div style="font-weight:600;">{{ $batch->uploader->name ?? '—' }}</div></div>
        <div><span style="color:var(--ink-soft);">Uploaded at</span><div style="font-weight:600;">{{ $currentTenant->localizeTime($batch->created_at)->format('M j, g:i A') }}</div></div>
        @if ($batch->reviewer)
            <div><span style="color:var(--ink-soft);">Reviewed by</span><div style="font-weight:600;">{{ $batch->reviewer->name }}</div></div>
            <div><span style="color:var(--ink-soft);">Reviewed at</span><div style="font-weight:600;">{{ $currentTenant->localizeTime($batch->reviewed_at)->format('M j, g:i A') }}</div></div>
        @endif
    </div>
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
                    @elseif ($row->status === 'duplicate' && $row->duplicateLead)
                        Matches <a href="{{ route('admin.crm.leads.show', $row->duplicateLead) }}">#{{ $row->duplicateLead->id }} {{ $row->duplicateLead->name }}</a>
                    @elseif ($row->imported_lead_id)
                        Imported as lead #{{ $row->imported_lead_id }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
