@extends('layouts.admin')

@section('title', ($tenant->name ?? 'Company').' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Good day, {{ auth()->user()->name }} 👋</h2>
        <p>Here's a snapshot of {{ $tenant->name ?? 'your company' }}, today.</p>
    </div>
    <!-- <div style="display:flex;gap:10px;">
        <div class="btn btn-ghost">Export</div>
        <div class="btn btn-primary">+ New Invoice</div>
    </div> -->
</div>

@if (($pendingLeadApprovals ?? 0) > 0 || ($pendingImports ?? 0) > 0)
    <div class="panel" style="padding:16px 20px;margin-bottom:18px;background:#FFF4E5;border-color:#FFF4E5;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="color:#B4690E;font-weight:700;font-size:13.5px;">
            Needs your attention:
            @if ($pendingLeadApprovals > 0)
                {{ $pendingLeadApprovals }} lead{{ $pendingLeadApprovals === 1 ? '' : 's' }} awaiting conversion approval
            @endif
            @if ($pendingLeadApprovals > 0 && $pendingImports > 0) &nbsp;·&nbsp; @endif
            @if ($pendingImports > 0)
                {{ $pendingImports }} lead import{{ $pendingImports === 1 ? '' : 's' }} awaiting review
            @endif
        </div>
         
    </div>
@endif

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Team Size</div>
        <div class="kpi-value">{{ $teamSize }}</div>
        <div class="kpi-delta">People in this company</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Checked In Today</div>
        <div class="kpi-value">{{ $todayAttendanceCount }}</div>
        <div class="kpi-delta up">Out of {{ $teamSize }} team members</div>
    </div>
    @if ($canViewHr)
        <div class="kpi-card">
            <div class="kpi-label">Attendance Rate</div>
            <div class="kpi-value">{{ $attendanceRate }}%</div>
            <div class="kpi-delta">Checked in today, of {{ $teamSize }}</div>
        </div>
    @endif
    @if ($canViewFinance)
        <div class="kpi-card">
            <div class="kpi-label">Pending Invoices</div>
            <div class="kpi-value">{{ $accountsStats['pending_count'] }}</div>
            <div class="kpi-delta">{{ number_format($accountsStats['pending_total'], 2) }} outstanding</div>
        </div>
    @endif
</div>

<div class="grid-2">
    <div class="panel" style="{{ $canViewCrm ? '' : 'grid-column:1 / -1;' }}">
        <div class="panel-head"><h3>Team Members</h3></div>
        <table>
            <tr><th>Name</th><th>Role</th><th>Designation</th><th>Today</th><th>Worked</th></tr>
            @forelse (($tenant->users ?? collect()) as $member)
                @php $att = $todayAttendanceByUser[$member->id] ?? null; @endphp
                <tr>
                    <td><strong>{{ $member->name }}</strong></td>
                    <td style="text-transform:capitalize;">{{ $member->role }}</td>
                    <td>{{ $member->designation->name ?? '—' }}</td>
                    <td>
                        @if ($att && $att->check_in)
                            @if ($att->isLate())
                                <span class="badge-pill" style="background:#FFF4E5;color:#B4690E;">Late</span>
                            @else
                                <span class="badge-pill pill-active">{{ $att->check_out ? 'Checked out' : 'Present' }}</span>
                            @endif
                        @else
                            <span class="badge-pill" style="background:var(--primary-soft);color:var(--primary-dark);">Not checked in</span>
                        @endif
                    </td>
                    <td>
                        @php $minutes = $att?->workedMinutes(); @endphp
                        {{ $minutes !== null ? number_format($minutes / 60, 1).'h' : '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:var(--ink-soft);">No team members yet.</td></tr>
            @endforelse
        </table>
    </div>

    @if ($canViewCrm)
        <div class="panel">
            <div class="panel-head">
                <h3>Recent Leads</h3>
                <a href="{{ route('admin.crm.leads.index') }}" class="link">View all</a>
            </div>
            @if ($leadStats)
                <div style="display:flex;gap:20px;padding:14px 20px;border-bottom:1px solid var(--line);">
                    <div>
                        <div style="font-size:11.5px;color:var(--ink-soft);font-weight:700;text-transform:uppercase;">Total Leads</div>
                        <div style="font-size:18px;font-weight:700;">{{ $leadStats['total'] }}</div>
                    </div>
                    <div>
                        <div style="font-size:11.5px;color:var(--ink-soft);font-weight:700;text-transform:uppercase;">New (7 days)</div>
                        <div style="font-size:18px;font-weight:700;">{{ $leadStats['new_this_week'] }}</div>
                    </div>
                </div>
            @endif
            @forelse ($recentLeads as $lead)
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--primary);"></span>
                    <div>
                        <div class="activity-text">
                            <strong>{{ $lead->name }}</strong>
                            @if ($lead->company_name) — {{ $lead->company_name }} @endif
                        </div>
                        <div class="activity-time">
                            {{ $crmSettings->stageLabel($lead->status) }} · {{ $lead->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:24px 20px;color:var(--ink-soft);font-size:13px;">No leads yet.</div>
            @endforelse
        </div>
    @endif
</div>

@if ($canViewFinance || $canViewProjects)
    <div class="grid-2" style="margin-top:18px;">
        @if ($canViewFinance)
            <div class="panel" style="{{ $canViewProjects ? '' : 'grid-column:1 / -1;' }}">
                <div class="panel-head">
                    <h3>Invoices Due This Month</h3>
                    <a href="{{ route('admin.finance.invoices.index') }}" class="link">View all</a>
                </div>
                @forelse ($dueInvoicesThisMonth as $invoice)
                    <div class="activity-item">
                        <span class="activity-dot" style="background:#B4690E;"></span>
                        <div>
                            <div class="activity-text">
                                <strong>{{ $invoice->invoice_number }}</strong> — {{ $invoice->client->name ?? '—' }}
                            </div>
                            <div class="activity-time">
                                Due {{ $invoice->due_date->format('j M Y') }} · {{ number_format((float) $invoice->total, 2) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:24px 20px;color:var(--ink-soft);font-size:13px;">No pending invoices due this month.</div>
                @endforelse
            </div>
        @endif

        @if ($canViewProjects)
            <div class="panel" style="{{ $canViewFinance ? '' : 'grid-column:1 / -1;' }}">
                <div class="panel-head">
                    <h3>Project Deadlines This Month</h3>
                    <a href="{{ route('admin.projects.index') }}" class="link">View all</a>
                </div>
                @forelse ($deadlineProjects as $project)
                    <div class="activity-item">
                        <span class="activity-dot" style="background:var(--primary);"></span>
                        <div>
                            <div class="activity-text"><strong>{{ $project->name }}</strong></div>
                            <div class="activity-time">
                                Due {{ $project->end_date->format('j M Y') }} · {{ $project->manager->name ?? 'Unassigned' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:24px 20px;color:var(--ink-soft);font-size:13px;">No project deadlines this month.</div>
                @endforelse
            </div>
        @endif
    </div>
@endif
@endsection
