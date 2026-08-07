@extends('layouts.admin')

@section('title', 'Journal Entry — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $entry->memo }}</h2>
        <p><a href="{{ route('admin.finance.ledger.entries.index') }}" style="color:var(--primary-dark);">← Back to Journal Entries</a></p>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $entry->entry_date->format('j M Y') }}</h3></div>
    <div style="padding:16px 20px;font-size:13px;color:var(--ink-soft);">
        Posted by {{ $entry->creator->name ?? 'System' }}
        @if ($entry->source_type)
            — from {{ class_basename($entry->source_type) }} #{{ $entry->source_id }}
        @endif
    </div>
    <table>
        <tr><th>Account</th><th>Debit</th><th>Credit</th></tr>
        @foreach ($entry->lines as $line)
            <tr>
                <td>{{ $line->chartOfAccount->code }} — {{ $line->chartOfAccount->name }}</td>
                <td>{{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '—' }}</td>
                <td>{{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '—' }}</td>
            </tr>
        @endforeach
        <tr style="font-weight:700;">
            <td>Total</td>
            <td>{{ number_format((float) $entry->lines->sum('debit'), 2) }}</td>
            <td>{{ number_format((float) $entry->lines->sum('credit'), 2) }}</td>
        </tr>
    </table>
</div>
@endsection
