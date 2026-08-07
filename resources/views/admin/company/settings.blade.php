@extends('layouts.admin')

@section('title', 'Company Settings — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Company Settings</h2>
        <p>Applies across the whole workspace — attendance, invoices and every other timestamp shown to your team.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<form method="POST" action="{{ route('admin.company.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="setup-shell">
        <div class="setup-nav">
            @foreach (['general' => 'General', 'monitoring' => 'Screen Monitoring'] as $key => $label)
                <button type="button" class="setup-nav-btn{{ $loop->first ? ' active' : '' }}" data-category="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="setup-panels">

            {{-- General --}}
            <div class="setup-cat" data-category="general">
                <div class="panel">
                    <div class="panel-head"><h3>Working Timezone</h3></div>
                    <div style="padding:18px 20px;">
                        <label class="f-label">Timezone</label>
                        <select class="f-input" name="timezone" required>
                            @foreach ($timezoneGroups as $region => $zones)
                                <optgroup label="{{ $region }}">
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone }}" @selected(old('timezone', $tenant->timezone) === $zone)>{{ str_replace('_', ' ', $zone) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="f-hint">Every check-in time, invoice date and report in this workspace is shown in this timezone. Current time here: <strong>{{ $tenant->localNow()->format('g:i A, l j F Y') }}</strong></p>
                    </div>
                </div>
            </div>

            {{-- Screen Monitoring --}}
            <div class="setup-cat" data-category="monitoring" style="display:none;">
                <div class="panel">
                    <div class="panel-head" style="justify-content:space-between;">
                        <h3>Screen Activity Monitoring</h3>
                        <a href="{{ route('admin.company.user-working.index') }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View Captures →</a>
                    </div>
                    <div style="padding:18px 20px;">
                        <div class="how-it-works">
                            <div class="how-it-works-title">How this actually works</div>
                            <ol>
                                <li>The settings below only set a <strong>schedule</strong> — they don't capture anything by themselves. The capture agent (a small program installed on each employee's PC) reads this schedule and follows it.</li>
                                <li><strong>No employee login is required for the agent.</strong> Instead, each employee's computer is matched by its <strong>hostname</strong> (its Windows machine name) — enter it once under <a href="{{ route('admin.hr.employees.index') }}">HR → Employees</a> → open the employee → Edit → "Workstation machine name". Find a PC's hostname by running <code>hostname</code> in its Command Prompt.</li>
                                <li>The agent runs with a small, always-visible <strong>tray icon</strong> — it's never hidden from the employee. On its schedule, it captures the employee's <strong>entire screen</strong> — the same as pressing "Print Screen" — including anything open at that moment: other software, browser tabs, social media, YouTube, etc., not just UperLevel.</li>
                                <li>Each capture uploads straight to UperLevel and is matched to the employee by hostname, then shows up under <a href="{{ route('admin.company.user-working.index') }}">Company → User Working</a>, filterable by employee and day.</li>
                            </ol>
                        </div>

                        <label class="f-check" style="margin-bottom:14px;">
                            <input type="hidden" name="capture_enabled" value="0">
                            <input type="checkbox" name="capture_enabled" value="1" id="captureEnabled" @checked(old('capture_enabled', $captureSettings->enabled))>
                            Enable screen activity monitoring
                        </label>
                        <p class="f-hint" style="margin:0 0 16px;">This only configures the schedule the capture agent will follow — UperLevel itself can't capture screens. Employees without a "Workstation machine name" set on their profile are never captured, even while this is on.</p>

                        <label class="f-label">Capture Schedule</label>
                        <div style="display:flex;gap:16px;margin-bottom:14px;">
                            <label class="f-check"><input type="radio" name="interval_mode" value="fixed" id="modeFixed" @checked(old('interval_mode', $captureSettings->interval_mode) === 'fixed')> Fixed interval</label>
                            <label class="f-check"><input type="radio" name="interval_mode" value="random" id="modeRandom" @checked(old('interval_mode', $captureSettings->interval_mode) === 'random')> Random interval</label>
                        </div>

                        <div id="fixedFields" style="margin-bottom:14px;">
                            <label class="f-label">Capture every</label>
                            <select class="f-input" name="interval_minutes">
                                @foreach ([15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour', 120 => '2 hours', 240 => '4 hours', 480 => '8 hours'] as $mins => $label)
                                    <option value="{{ $mins }}" @selected(old('interval_minutes', $captureSettings->interval_minutes) == $mins)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="randomFields" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                            <div>
                                <label class="f-label">Minimum minutes</label>
                                <input class="f-input" type="number" min="1" max="1440" name="random_min_minutes" value="{{ old('random_min_minutes', $captureSettings->random_min_minutes) }}">
                            </div>
                            <div>
                                <label class="f-label">Maximum minutes</label>
                                <input class="f-input" type="number" min="1" max="1440" name="random_max_minutes" value="{{ old('random_max_minutes', $captureSettings->random_max_minutes) }}">
                            </div>
                        </div>

                        <div style="margin-bottom:14px;">
                            <label class="f-label">Keep screenshots for — days</label>
                            <input class="f-input" type="number" min="1" max="3650" name="retention_days" value="{{ old('retention_days', $captureSettings->retention_days) }}" placeholder="Leave blank to keep forever" style="max-width:200px;">
                            <p class="f-hint">Older screenshots are deleted automatically. Leave blank to keep them indefinitely.</p>
                        </div>

                        <label class="f-check">
                            <input type="hidden" name="notify_employees" value="0">
                            <input type="checkbox" name="notify_employees" value="1" id="notifyEmployees" @checked(old('notify_employees', $captureSettings->notify_employees))>
                            Show employees a notice that screen activity is monitored
                        </label>
                        <p class="f-hint" id="notifyPreviewHint" style="margin-top:8px;">When this is on, every employee sees this banner on their dashboard: <span class="notify-preview">🖥 This workspace periodically captures screen activity during working hours for productivity monitoring.</span></p>
                        <p class="f-hint" id="notifyOffHint" style="margin-top:8px;display:none;">When this is off, employees see no indication that monitoring is active.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary">Save Company Settings</button>
    </div>
</form>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:10px 0 0;}
    .f-check{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--ink);cursor:pointer;}
    .how-it-works{background:var(--bg);border:1px solid var(--line);border-radius:8px;padding:14px 16px;margin-bottom:18px;}
    .how-it-works-title{font-size:11.5px;font-weight:700;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.02em;margin-bottom:8px;}
    .how-it-works ol{margin:0;padding-left:18px;font-size:12.5px;color:var(--ink);line-height:1.6;}
    .how-it-works li{margin-bottom:4px;}
    .how-it-works a{color:var(--primary-dark);}
    .how-it-works code{background:#fff;border:1px solid var(--line);padding:1px 6px;border-radius:4px;font-size:11.5px;}
    .notify-preview{display:inline-block;margin-top:4px;background:#FFF4E5;color:#B4690E;font-weight:600;padding:6px 10px;border-radius:6px;}

    .setup-shell{display:grid;grid-template-columns:190px 1fr;gap:20px;align-items:start;}
    .setup-nav{display:flex;flex-direction:column;gap:4px;position:sticky;top:16px;}
    .setup-nav-btn{text-align:left;padding:10px 14px;border-radius:8px;border:none;background:transparent;color:var(--ink-soft);font-size:13px;font-weight:600;cursor:pointer;}
    .setup-nav-btn:hover{background:var(--bg);}
    .setup-nav-btn.active{background:var(--primary-soft);color:var(--primary-dark);}
    @media (max-width: 800px){ .setup-shell{grid-template-columns:1fr;} .setup-nav{flex-direction:row;flex-wrap:wrap;position:static;} }
</style>

<script>
    // Named setupNavBtns/setupCatPanels (not navBtns) — the shared header
    // partial (partials/admin-subnav.blade.php) already declares a global
    // `const navBtns` on every admin page; reusing that name here throws a
    // SyntaxError that silently kills this entire script block.
    const setupNavBtns = document.querySelectorAll('.setup-nav-btn');
    const setupCatPanels = document.querySelectorAll('.setup-cat');
    setupNavBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setupNavBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            setupCatPanels.forEach(p => { p.style.display = p.dataset.category === btn.dataset.category ? 'block' : 'none'; });
        });
    });

    (function () {
        var fixedFields = document.getElementById('fixedFields');
        var randomFields = document.getElementById('randomFields');
        var modeFixed = document.getElementById('modeFixed');
        var modeRandom = document.getElementById('modeRandom');

        function toggle() {
            fixedFields.style.display = modeFixed.checked ? '' : 'none';
            randomFields.style.display = modeRandom.checked ? '' : 'none';
        }

        modeFixed.addEventListener('change', toggle);
        modeRandom.addEventListener('change', toggle);
        toggle();
    })();

    (function () {
        var notifyEmployees = document.getElementById('notifyEmployees');
        var previewHint = document.getElementById('notifyPreviewHint');
        var offHint = document.getElementById('notifyOffHint');

        function toggleNotifyPreview() {
            previewHint.style.display = notifyEmployees.checked ? '' : 'none';
            offHint.style.display = notifyEmployees.checked ? 'none' : '';
        }

        notifyEmployees.addEventListener('change', toggleNotifyPreview);
        toggleNotifyPreview();
    })();
</script>
@endsection
