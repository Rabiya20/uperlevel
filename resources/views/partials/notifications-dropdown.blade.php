@php
    $unreadNotifications = auth()->user()->unreadNotifications()->latest()->take(10)->get();
    $unreadNotifCount = auth()->user()->unreadNotifications()->count();
@endphp
<div class="profile-wrap" id="notifWrap">
    <div class="icon-btn" style="position:relative;cursor:pointer;" onclick="document.getElementById('notifMenu').classList.toggle('open')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
        @if ($unreadNotifCount > 0)
            <span class="notif-badge">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
        @endif
    </div>
    <div class="dropdown" id="notifMenu" style="width:320px;">
        <div class="dropdown-user" style="display:flex;justify-content:space-between;align-items:center;">
            <div class="n">Notifications</div>
            @if ($unreadNotifCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--primary-dark);font-size:11.5px;font-weight:700;cursor:pointer;">Mark all read</button>
                </form>
            @endif
        </div>
        @forelse ($unreadNotifications as $notification)
            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                @csrf
                <button type="submit" class="dd-item" style="text-align:left;white-space:normal;">
                    {{ $notification->data['message'] ?? 'Notification' }}
                    <div style="font-size:10.5px;color:var(--ink-soft);margin-top:2px;">{{ $notification->created_at->diffForHumans() }}</div>
                </button>
            </form>
        @empty
            <div style="padding:16px 14px;font-size:12.5px;color:var(--ink-soft);text-align:center;">You're all caught up.</div>
        @endforelse
    </div>
</div>

<style>
    .notif-badge{position:absolute;top:-4px;right:-4px;background:#C0392B;color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 4px;}
</style>

<script>
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#notifWrap')) {
            const dd = document.getElementById('notifMenu');
            if (dd) dd.classList.remove('open');
        }
    });
</script>
