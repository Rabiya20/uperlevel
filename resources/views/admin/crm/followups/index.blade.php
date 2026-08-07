@extends('layouts.admin')

@section('title', 'Follow-ups — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Follow-ups</h2>
        <p>Every open follow-up across the team, soonest first. Missed ones are highlighted.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $followups->total() }} open follow-up{{ $followups->total() === 1 ? '' : 's' }}</h3></div>
    @if ($followups->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">Nothing open — every follow-up is done.</div>
    @else
        <table>
            <tr><th>When</th><th>Lead</th><th>Owner</th><th>Notes</th><th></th></tr>
            @foreach ($followups as $followup)
                <tr style="{{ $followup->isMissed() ? 'background:#FDEEEC;' : '' }}">
                    <td>
                        {{ $currentTenant->localizeTime($followup->follow_up_at)->format('M j, g:i A') }}
                        @if ($followup->isMissed())
                            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;margin-left:6px;">Missed</span>
                        @endif
                    </td>
                    <td>{{ $followup->lead->name }}</td>
                    <td>{{ $followup->employee->name ?? '—' }}</td>
                    <td>{{ $followup->notes ?? '—' }}</td>
                    <td><a href="{{ route('admin.crm.leads.show', $followup->lead) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Open lead</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($followups->hasPages())
    <div style="margin-top:16px;">{{ $followups->links() }}</div>
@endif
@endsection
