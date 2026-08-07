<div class="panel">
    <div class="panel-head"><h3>Follow-ups</h3></div>

    @if ($lead->canEdit(auth()->user()))
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
            <form method="POST" action="{{ route("$routePrefix.followups.store", $lead) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <div style="flex:1;min-width:160px;">
                    <label class="f-label">Follow up on</label>
                    <input class="f-input" type="datetime-local" name="follow_up_at" required>
                </div>
                <div style="flex:2;min-width:200px;">
                    <label class="f-label">Notes — optional</label>
                    <input class="f-input" type="text" name="notes" maxlength="1000">
                </div>
                <button type="submit" class="btn btn-ghost" style="padding:9px 14px;font-size:12.5px;">Schedule</button>
            </form>
        </div>
    @endif

    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
        @forelse ($lead->followups as $followup)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 11px;border:1px solid var(--line);border-radius:8px;{{ $followup->isMissed() ? 'background:#FDEEEC;' : '' }}">
                <div>
                    <div style="font-weight:700;font-size:13px;{{ $followup->completed_at ? 'text-decoration:line-through;color:var(--ink-soft);' : '' }}">
                        {{ $currentTenant->localizeTime($followup->follow_up_at)->format('M j, g:i A') }}
                        @if ($followup->isMissed())
                            <span class="badge-pill" style="background:#FDEEEC;color:#C0392B;margin-left:6px;">Missed</span>
                        @elseif ($followup->completed_at)
                            <span class="badge-pill pill-active" style="margin-left:6px;">Done</span>
                        @endif
                    </div>
                    @if ($followup->notes)
                        <div style="font-size:12.5px;color:var(--ink-soft);">{{ $followup->notes }}</div>
                    @endif
                </div>
                @if (! $followup->completed_at)
                    <form method="POST" action="{{ route("$routePrefix.followups.complete", [$lead, $followup]) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Mark done</button>
                    </form>
                @endif
            </div>
        @empty
            <p style="color:var(--ink-soft);font-size:13px;margin:0;">No follow-ups scheduled.</p>
        @endforelse
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
</style>
