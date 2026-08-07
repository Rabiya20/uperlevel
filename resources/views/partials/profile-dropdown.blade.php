@php($user = auth()->user())
<div class="profile-wrap">
    <div class="profile-chip" onclick="document.getElementById('ddMenu').classList.toggle('open')">
        <div style="position:relative;">
            <img class="avatar-img" src="https://i.pravatar.cc/64?u={{ $user->email }}" alt="">
            <span class="status-dot"></span>
        </div>
        <div>
            <div class="name">{{ $user->name }}</div>
            <div class="role">{{ $user->designation->name ?? ucfirst($user->role) }}</div>
        </div>
    </div>
    <div class="dropdown" id="ddMenu">
        <div class="dropdown-user">
            <div class="n">{{ $user->name }}</div>
            <div class="e">{{ $user->email }}</div>
        </div>

        @if (!$user->isSuperAdmin() && ($todayAttendance ?? null))
            <div class="checkin-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                Checked in at {{ $currentTenant->localizeTime($todayAttendance->check_in)->format('g:i A') }}
            </div>
        @endif

        <button type="button" class="dd-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
            Profile Settings
        </button>

        @if (!$user->isSuperAdmin())
            <form method="POST" action="{{ route('attendance.check-out') }}">
                @csrf
                <button type="submit" class="dd-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    Check-out
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dd-item danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Logout
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-wrap')) {
            const dd = document.getElementById('ddMenu');
            if (dd) dd.classList.remove('open');
        }
    });
</script>
