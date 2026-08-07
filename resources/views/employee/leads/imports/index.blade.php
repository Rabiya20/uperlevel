@extends('layouts.employee')

@section('title', 'Import History — UperLevel')
@section('page-title', 'Import History')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Import History</h2>
        <p><a href="{{ route('employee.leads.index') }}" style="color:var(--primary-dark);">← Back to My Leads</a></p>
    </div>
    <a href="{{ route('employee.leads.import.create') }}" class="btn btn-primary">+ Import Leads</a>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@php
    $statusColors = [
        'pending_review' => ['bg' => '#FFF4E5', 'fg' => '#B4690E', 'label' => 'Pending Review'],
        'completed' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50', 'label' => 'Approved & Imported'],
        'rejected' => ['bg' => '#FDEEEC', 'fg' => '#C0392B', 'label' => 'Rejected'],
    ];
@endphp

<div class="panel">
    <div class="panel-head"><h3>{{ $batches->total() }} upload{{ $batches->total() === 1 ? '' : 's' }}</h3></div>
    @if ($batches->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">You haven't uploaded any spreadsheets yet.</div>
    @else
        <table>
            <tr><th>File</th><th>Status</th><th>Rows</th><th>Date</th><th></th></tr>
            @foreach ($batches as $batch)
                @php $sc = $statusColors[$batch->status] ?? ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)', 'label' => ucfirst($batch->status)]; @endphp
                <tr>
                    <td><strong>{{ $batch->original_filename }}</strong></td>
                    <td><span class="badge-pill" style="background:{{ $sc['bg'] }};color:{{ $sc['fg'] }};">{{ $sc['label'] }}</span></td>
                    <td>{{ $batch->valid_rows }} valid, {{ $batch->duplicate_rows }} dup, {{ $batch->error_rows }} error</td>
                    <td>{{ $currentTenant->localizeTime($batch->created_at)->format('M j, g:i A') }}</td>
                    <td><a href="{{ route('employee.leads.imports.show', $batch) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($batches->hasPages())
    <div style="margin-top:16px;">{{ $batches->links() }}</div>
@endif
@endsection
