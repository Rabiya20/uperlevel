@php($user = auth()->user())
@if (!$user->isSuperAdmin() && ($todayAttendance ?? null))
<div class="checkin-banner">
    <div class="l">
        <div class="checkin-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <div>
            <div class="t1">
                You were automatically checked in today at {{ $currentTenant->localizeTime($todayAttendance->check_in)->format('g:i A') }}
                @if ($todayAttendance->isLate())
                    <span style="color:#FFD9A0;font-weight:700;">— running a bit late</span>
                @endif
            </div>
            <div class="t2">
                @if ($todayAttendance->check_out)
                    You checked out at {{ $currentTenant->localizeTime($todayAttendance->check_out)->format('g:i A') }}
                @else
                    Active session — don't forget to check out at day's end
                @endif
            </div>
        </div>
    </div>

    @if ($showCheckoutButton ?? false)
        @if (!$todayAttendance->check_out)
            <form method="POST" action="{{ route('attendance.check-out') }}">
                @csrf
                <button type="submit" class="btn-checkout">Check Out</button>
            </form>
        @endif
    @else
        <div class="r">{{ $teamPresentLabel ?? '' }}</div>
    @endif
</div>
@endif
