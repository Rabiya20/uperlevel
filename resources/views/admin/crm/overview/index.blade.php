@extends('layouts.admin')

@section('title', 'Leads Overview — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Leads Overview</h2>
        <p>A snapshot of the whole pipeline — where leads stand, who's converting them, and what's happened recently.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-ghost">All Leads</a>
        <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-primary">+ Add Lead</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total Leads</div>
        <div class="kpi-value">{{ $totalLeads }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">New This Week</div>
        <div class="kpi-value">{{ $newThisWeek }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">In Pipeline</div>
        <div class="kpi-value">{{ $inPipeline }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value">{{ $conversionRate }}%</div>
        <div class="kpi-delta">{{ $wonCount }} won · {{ $lostCount }} lost</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px;">
    <div class="panel">
        <div class="panel-head"><h3>Pipeline by stage</h3></div>
        <table>
            <tr><th>Stage</th><th>Leads</th></tr>
            @foreach ($stageBreakdown as $stage)
                <tr>
                    <td>{{ $stage['label'] }}</td>
                    <td>{{ $stage['count'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="panel">
        <div class="panel-head"><h3>Top assignees</h3></div>
        @if ($topAssignees->isEmpty())
            <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leads assigned yet.</div>
        @else
            <table>
                <tr><th>Employee</th><th>Leads</th><th>Won</th></tr>
                @foreach ($topAssignees as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['won'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>

<div class="panel" style="margin-top:18px;">
    <div class="panel-head"><h3>Recent activity</h3></div>
    @if ($recentActivity->isEmpty())
        <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">Nothing has happened yet.</div>
    @else
        <table>
            <tr><th>When</th><th>Lead</th><th>By</th><th>Details</th></tr>
            @foreach ($recentActivity as $entry)
                <tr>
                    <td style="white-space:nowrap;">{{ $currentTenant->localizeTime($entry->created_at)->format('j M Y, g:i A') }}</td>
                    <td>{{ $entry->lead->name ?? '—' }}</td>
                    <td>{{ $entry->user->name ?? 'System' }}</td>
                    <td>{{ $entry->description }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
