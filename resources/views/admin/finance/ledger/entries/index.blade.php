@extends('layouts.admin')

@section('title', 'Journal Entries — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Journal Entries</h2>
        <p><a href="{{ route('admin.finance.ledger.index') }}" style="color:var(--primary-dark);">← Back to Ledger</a></p>
    </div>
    <a href="{{ route('admin.finance.ledger.entries.create') }}" class="btn btn-primary">+ New Entry</a>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="panel">
    @if ($entries->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No journal entries yet.</div>
    @else
        <table>
            <tr><th>Date</th><th>Memo</th><th>Source</th><th>Lines</th><th>Posted By</th><th></th></tr>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date->format('j M Y') }}</td>
                    <td>{{ $entry->memo }}</td>
                    <td>{{ $entry->source_type ? class_basename($entry->source_type) : 'Manual' }}</td>
                    <td>{{ $entry->lines_count }}</td>
                    <td>{{ $entry->creator->name ?? '—' }}</td>
                    <td><a href="{{ route('admin.finance.ledger.entries.show', $entry) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($entries->hasPages())
    <div style="margin-top:16px;">{{ $entries->links() }}</div>
@endif
@endsection
