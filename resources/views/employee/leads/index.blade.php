@extends('layouts.employee')

@section('title', 'My Leads — UperLevel')
@section('page-title', 'My Leads')

@section('content-body')
<div class="page-head">
    <div>
        <h2>My Leads</h2>
        <p>Leads assigned to you — work them through the pipeline.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('employee.leads.imports.index') }}" class="btn btn-ghost">Import History</a>
        <a href="{{ route('employee.leads.import.create') }}" class="btn btn-ghost">Import Leads</a>
        <a href="{{ route('employee.leads.create') }}" class="btn btn-primary">+ Add Lead</a>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Assigned Leads</div>
        <div class="kpi-value">{{ $assignedCount }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Follow-ups Today</div>
        <div class="kpi-value">{{ $followupsToday }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value">{{ $conversionRate }}%</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Won / Lost</div>
        <div class="kpi-value">{{ $wonCount }} / {{ $lostCount }}</div>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Name, email, company…">
        </div>
        <div>
            <label class="f-label">Status</label>
            <select class="f-input" name="status">
                <option value="">All</option>
                @foreach ($settings->pipeline_stages as $s)
                    <option value="{{ $s['key'] }}" @selected(request('status') === $s['key'])>{{ $s['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $leads->total() }} lead{{ $leads->total() === 1 ? '' : 's' }}</h3></div>
    @if ($leads->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leads assigned to you yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Company</th><th>Source</th><th>Status</th><th>Budget</th><th></th></tr>
            @foreach ($leads as $lead)
                @php $stage = $settings->stage($lead->status); @endphp
                <tr>
                    <td><strong>{{ $lead->name }}</strong></td>
                    <td>{{ $lead->company_name ?? '—' }}</td>
                    <td>{{ $lead->source ?? '—' }}</td>
                    <td>
                        @php $badgeBg = $stage['is_won'] ?? false ? '#E7F7EE' : ($stage['is_lost'] ?? false ? '#FDEEEC' : 'var(--primary-soft)'); $badgeFg = $stage['is_won'] ?? false ? '#0F7C50' : ($stage['is_lost'] ?? false ? '#C0392B' : 'var(--primary-dark)'); @endphp
                        @if ($lead->canEdit(auth()->user()) && ! ($lead->is_locked && $lead->conversion_status === 'pending_approval'))
                            <form method="POST" action="{{ route('employee.leads.status', $lead) }}" style="display:inline-block;">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="status-select" style="background:{{ $badgeBg }};color:{{ $badgeFg }};" title="Pick a stage to update it right away">
                                    @foreach ($settings->pipeline_stages as $s)
                                        <option value="{{ $s['key'] }}" @selected($s['key'] === $lead->status)>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="badge-pill" style="background:{{ $badgeBg }};color:{{ $badgeFg }};">{{ $stage['label'] ?? $lead->status }}</span>
                        @endif
                        @if ($lead->conversion_status === 'pending_approval')
                            <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">Pending approval</span>
                        @endif
                    </td>
                    <td>{{ $lead->budget !== null ? $lead->currency.' '.number_format((float) $lead->budget, 2) : '—' }}</td>
                    <td><a href="{{ route('employee.leads.show', $lead) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($leads->hasPages())
    <div style="margin-top:16px;">{{ $leads->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .status-select{padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700;border:none;cursor:pointer;}
</style>
@endsection
