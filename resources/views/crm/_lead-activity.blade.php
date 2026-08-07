@php
    $activityLabels = [
        'created' => 'Created',
        'status_change' => 'Status change',
        'note' => 'Note',
        'call' => 'Call',
        'assignment' => 'Assignment',
        'updated' => 'Updated',
        'conversion_request' => 'Approval requested',
        'converted' => 'Converted',
        'conversion_rejected' => 'Approval rejected',
    ];
@endphp

<div class="panel">
    <div class="panel-head"><h3>Activity</h3></div>

    @if ($lead->canEdit(auth()->user()))
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
            <form method="POST" action="{{ route("$routePrefix.activity", $lead) }}">
                @csrf
                <div style="display:flex;gap:10px;margin-bottom:8px;">
                    <label class="f-check"><input type="radio" name="type" value="note" checked> Note</label>
                    <label class="f-check"><input type="radio" name="type" value="call"> Call log</label>
                </div>
                <textarea class="f-input" name="description" rows="2" placeholder="What happened?" required></textarea>
                <div style="margin-top:8px;text-align:right;">
                    <button type="submit" class="btn btn-ghost" style="padding:7px 14px;font-size:12.5px;">Add</button>
                </div>
            </form>
        </div>
    @endif

    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;max-height:420px;overflow-y:auto;">
        @forelse ($lead->activities as $activity)
            <div class="activity-item">
                <span class="activity-dot" style="background:var(--primary);"></span>
                <div>
                    <div class="activity-text">
                        <strong>{{ $activityLabels[$activity->type] ?? ucfirst($activity->type) }}</strong>
                        @if ($activity->user)
                            <span style="color:var(--ink-soft);">— {{ $activity->user->name }}</span>
                        @endif
                    </div>
                    @if ($activity->description)
                        <div class="activity-text">{{ $activity->description }}</div>
                    @endif
                    <div class="activity-time">{{ $currentTenant->localizeTime($activity->created_at)->format('M j, g:i A') }}</div>
                </div>
            </div>
        @empty
            <p style="color:var(--ink-soft);font-size:13px;margin:0;">No activity yet.</p>
        @endforelse
    </div>
</div>

<style>
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-check{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;color:var(--ink);}
</style>
