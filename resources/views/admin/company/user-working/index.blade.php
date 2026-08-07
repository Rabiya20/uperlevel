@extends('layouts.admin')

@section('title', 'User Working — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>User Working</h2>
        <p>Screen activity captured by each employee's monitoring agent — defaults to today, every employee.</p>
    </div>
    <a href="{{ route('admin.company.settings') }}" class="btn btn-ghost">Capture Settings</a>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="f-label">Employee</label>
            <select class="f-input" name="user_id">
                <option value="">All Employees</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selectedUserId == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Date</label>
            <input class="f-input" type="date" name="date" value="{{ $date->format('Y-m-d') }}">
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

@if ($groups->isEmpty())
    <div class="panel" style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No screenshots for {{ $date->format('j M Y') }}{{ $selectedUserId ? ' for this employee' : '' }} yet.
    </div>
@else
    @foreach ($groups as $group)
        <div class="panel" style="margin-bottom:18px;">
            <div class="panel-head" style="justify-content:space-between;">
                <h3>{{ $group['user']->name ?? 'Unknown' }} — {{ $group['captures']->count() }} screenshot{{ $group['captures']->count() === 1 ? '' : 's' }}</h3>
                <form method="POST" action="{{ route('admin.company.user-working.revoke-agent', $group['user']) }}" onsubmit="return confirm('Revoke this employee\'s screen-capture agent access? They\'ll need to sign in to the agent again.');">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#C0392B;">Revoke Agent Access</button>
                </form>
            </div>
            <div class="capture-grid">
                @foreach ($group['captures'] as $capture)
                    <div class="capture-card">
                        <button type="button" class="capture-thumb-btn" data-lightbox-src="{{ route('admin.company.user-working.view', $capture) }}" data-lightbox-caption="{{ $group['user']->name ?? 'Unknown' }} — {{ $currentTenant->localizeTime($capture->captured_at)->format('j M Y, g:i A') }}">
                            <img src="{{ route('admin.company.user-working.view', $capture) }}" alt="Screenshot" loading="lazy">
                        </button>
                        <div class="capture-meta">
                            <span>{{ $currentTenant->localizeTime($capture->captured_at)->format('g:i A') }}</span>
                            <form method="POST" action="{{ route('admin.company.user-working.destroy', $capture) }}" onsubmit="return confirm('Delete this screenshot?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="capture-delete" title="Delete">✕</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

{{-- Lightbox overlay --}}
<div id="lightbox" class="lightbox" hidden>
    <button type="button" class="lightbox-close" id="lightboxClose" title="Close (Esc)">✕</button>
    <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" title="Previous">‹</button>
    <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" title="Next">›</button>
    <img id="lightboxImg" src="" alt="Screenshot preview">
    <div id="lightboxCaption" class="lightbox-caption"></div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .capture-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;padding:18px 20px;}
    .capture-card{border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--bg);}
    .capture-card img{width:100%;height:110px;object-fit:cover;display:block;background:#e9ecef;}
    .capture-meta{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;font-size:11.5px;color:var(--ink-soft);}
    .capture-delete{border:none;background:none;color:#C0392B;cursor:pointer;font-size:12px;padding:0;}
    .capture-thumb-btn{display:block;width:100%;padding:0;border:none;background:none;cursor:zoom-in;}

    .lightbox{position:fixed;inset:0;background:rgba(10,12,16,.92);z-index:200;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;}
    .lightbox[hidden]{display:none;}
    .lightbox img{max-width:100%;max-height:calc(100vh - 120px);object-fit:contain;border-radius:6px;box-shadow:0 12px 40px rgba(0,0,0,.5);}
    .lightbox-caption{color:#fff;font-size:13px;font-weight:600;margin-top:14px;}
    .lightbox-close{position:absolute;top:20px;right:24px;background:rgba(255,255,255,.12);border:none;color:#fff;width:38px;height:38px;border-radius:50%;font-size:16px;cursor:pointer;}
    .lightbox-close:hover{background:rgba(255,255,255,.22);}
    .lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:26px;line-height:1;cursor:pointer;}
    .lightbox-nav:hover{background:rgba(255,255,255,.22);}
    .lightbox-prev{left:24px;}
    .lightbox-next{right:24px;}
</style>

<script>
    (function () {
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightboxImg');
        const lightboxCaption = document.getElementById('lightboxCaption');
        const thumbs = Array.from(document.querySelectorAll('.capture-thumb-btn'));
        let currentIndex = -1;

        function openAt(index) {
            if (index < 0 || index >= thumbs.length) return;
            currentIndex = index;
            const btn = thumbs[currentIndex];
            lightboxImg.src = btn.dataset.lightboxSrc;
            lightboxCaption.textContent = btn.dataset.lightboxCaption || '';
            lightbox.hidden = false;
        }

        function close() {
            lightbox.hidden = true;
            lightboxImg.src = '';
        }

        thumbs.forEach((btn, index) => {
            btn.addEventListener('click', () => openAt(index));
        });

        document.getElementById('lightboxClose').addEventListener('click', close);
        document.getElementById('lightboxPrev').addEventListener('click', () => openAt(currentIndex - 1));
        document.getElementById('lightboxNext').addEventListener('click', () => openAt(currentIndex + 1));

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) close();
        });

        document.addEventListener('keydown', (e) => {
            if (lightbox.hidden) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') openAt(currentIndex - 1);
            if (e.key === 'ArrowRight') openAt(currentIndex + 1);
        });
    })();
</script>
@endsection
