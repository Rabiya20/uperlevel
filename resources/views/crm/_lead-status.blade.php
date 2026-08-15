@php
    $stage = $settings->stage($lead->status);
    $stageColors = ['won' => ['bg' => '#E7F7EE', 'fg' => '#0F7C50'], 'lost' => ['bg' => '#FDEEEC', 'fg' => '#C0392B']];
    $badge = $stage['is_won'] ?? false ? $stageColors['won'] : ($stage['is_lost'] ?? false ? $stageColors['lost'] : ['bg' => 'var(--primary-soft)', 'fg' => 'var(--primary-dark)']);
    // Only the pending-approval lock genuinely blocks a direct status
    // change (that's an active handoff — Approve/Reject decide it). Once
    // a lead is Won and converted, the dropdown stays available for anyone
    // who can edit the lead, so a mistaken "Won" is never a dead end.
    $pendingApproval = $lead->is_locked && $lead->conversion_status === 'pending_approval';
@endphp

<div class="panel">
    <div class="panel-head"><h3>Status</h3></div>
    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
        @if ($pendingApproval)
            <span class="badge-pill" style="background:{{ $badge['bg'] }};color:{{ $badge['fg'] }};align-self:flex-start;">{{ $stage['label'] ?? $lead->status }}</span>
            <div style="background:#FFF4E5;border-radius:8px;padding:12px;font-size:12.5px;color:#B4690E;">
                Locked — waiting on admin approval to convert to a client.
            </div>
            @if ($isAdmin)
                <div style="display:flex;gap:10px;">
                    <form method="POST" action="{{ route('admin.crm.leads.approve', $lead) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;">Approve & Convert</button>
                    </form>
                    <form method="POST" action="{{ route('admin.crm.leads.reject', $lead) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost" style="padding:8px 14px;font-size:12.5px;color:#c0392b;">Reject</button>
                    </form>
                </div>
            @endif
        @else
            @if ($lead->conversion_status === 'approved')
                <div style="background:var(--success-soft);border-radius:8px;padding:12px;font-size:12.5px;color:#0F7C50;">
                    Converted to a client.
                    @if ($isAdmin && $lead->client_id)
                        <a href="{{ route('admin.company.clients.show', $lead->client_id) }}" style="color:#0F7C50;font-weight:700;text-decoration:underline;margin-left:6px;">View client →</a>
                    @endif
                </div>
            @endif

            @if ($lead->canEdit(auth()->user()))
                <form method="POST" action="{{ route("$routePrefix.status", $lead) }}">
                    @csrf
                    <select class="status-select" name="status" onchange="this.form.submit()" style="background:{{ $badge['bg'] }};color:{{ $badge['fg'] }};">
                        @foreach ($settings->pipeline_stages as $s)
                            <option value="{{ $s['key'] }}" @selected($s['key'] === $lead->status)>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:11px;color:var(--ink-soft);margin:6px 0 0;">Pick a stage to update it right away — no separate save step.</p>
                </form>
            @else
                <span class="badge-pill" style="background:{{ $badge['bg'] }};color:{{ $badge['fg'] }};align-self:flex-start;">{{ $stage['label'] ?? $lead->status }}</span>
            @endif
        @endif
    </div>
</div>

<style>
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .status-select{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:20px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;}
</style>
