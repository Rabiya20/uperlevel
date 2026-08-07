@extends('layouts.admin')

@section('title', 'CRM Setup — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Leads / CRM — Setup</h2>
        <p>Defines the pipeline, sources, assignment rules and approval flow every lead in this company follows.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.crm.settings.update') }}" id="crmForm">
    @csrf
    @method('PUT')

    <div class="setup-shell">
        <div class="setup-nav">
            @foreach (['pipeline' => 'Pipeline', 'sources' => 'Sources', 'assignment' => 'Assignment', 'approval' => 'Approval & Duplicates'] as $key => $label)
                <button type="button" class="setup-nav-btn{{ $loop->first ? ' active' : '' }}" data-category="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="setup-panels">

            {{-- Pipeline --}}
            <div class="setup-cat" data-category="pipeline">
                <div class="panel">
                    <div class="panel-head"><h3>Pipeline Stages</h3></div>
                    <div style="padding:16px 20px;">
                        <p class="f-hint" style="margin:0 0 10px;">Pick exactly one stage as "Won" and one as "Lost" — reaching Won triggers the approval/conversion flow.</p>
                        <div id="stageRows" style="display:flex;flex-direction:column;gap:8px;"></div>
                        <button type="button" class="btn btn-ghost" id="addStageRow" style="margin-top:10px;padding:7px 12px;font-size:12.5px;">+ Add stage</button>
                    </div>
                </div>
            </div>

            {{-- Sources --}}
            <div class="setup-cat" data-category="sources" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Lead Sources</h3></div>
                    <div style="padding:16px 20px;">
                        <div id="sourceRows" style="display:flex;flex-direction:column;gap:8px;"></div>
                        <button type="button" class="btn btn-ghost" id="addSourceRow" style="margin-top:10px;padding:7px 12px;font-size:12.5px;">+ Add source</button>
                    </div>
                </div>
            </div>

            {{-- Assignment --}}
            <div class="setup-cat" data-category="assignment" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Lead Assignment</h3></div>
                    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                        @foreach (['manual' => ['Manual Assignment', 'Admin assigns each lead by hand.'], 'round_robin' => ['Round Robin', 'Auto-distribute evenly among employees — coming soon, leads stay unassigned until then.'], 'rule_based' => ['Rule-Based', 'Auto-assign by country, source or budget — coming soon, leads stay unassigned until then.']] as $key => [$label, $hint])
                            <label class="f-check" style="align-items:flex-start;">
                                <input type="radio" name="assignment_mode" value="{{ $key }}" style="margin-top:2px;" @checked(old('assignment_mode', $settings->assignment_mode) === $key)>
                                <span>
                                    <span style="display:block;">{{ $label }}</span>
                                    <span class="f-hint" style="margin:2px 0 0;">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Approval & Duplicates --}}
            <div class="setup-cat" data-category="approval" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Approval & Duplicates</h3></div>
                    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
                        <label class="f-check">
                            <input type="hidden" name="approval_required" value="0">
                            <input type="checkbox" name="approval_required" value="1" @checked(old('approval_required', $settings->approval_required))>
                            Admin approval required before a lead converts to a client
                        </label>
                        <div>
                            <label class="f-label">Duplicate leads (matching email, phone or company)</label>
                            <select class="f-input" name="duplicate_handling">
                                <option value="skip" @selected(old('duplicate_handling', $settings->duplicate_handling) === 'skip')>Skip — block creation, point to the existing lead</option>
                                <option value="allow" @selected(old('duplicate_handling', $settings->duplicate_handling) === 'allow')>Allow — create anyway</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="submit" class="btn btn-primary">Save CRM Settings</button>
    </div>
</form>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:8px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
    .repeat-row{display:flex;align-items:center;gap:10px;border:1px solid var(--line);border-radius:8px;padding:9px 11px;background:var(--bg);}
    .repeat-row input[type=text]{flex:1;}
    .repeat-row .remove-row{background:none;border:none;color:var(--ink-soft);cursor:pointer;font-size:16px;line-height:1;padding:2px 6px;}
    .repeat-row .remove-row:hover{color:#c0392b;}
    .repeat-row label{display:flex;align-items:center;gap:4px;font-size:11px;color:var(--ink-soft);white-space:nowrap;}

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

    let stageIndex = 0;
    const stageRows = document.getElementById('stageRows');

    function addStageRow(stage) {
        stage = stage || {};
        const i = stageIndex++;
        const row = document.createElement('div');
        row.className = 'repeat-row';
        row.innerHTML = `
            ${stage.key ? `<input type="hidden" name="pipeline_stages[${i}][key]" value="${stage.key}">` : ''}
            <input type="text" name="pipeline_stages[${i}][label]" value="${stage.label || ''}" placeholder="Stage name" required>
            <label><input type="radio" name="won_selector" ${stage.is_won ? 'checked' : ''}> Won</label>
            <input type="hidden" name="pipeline_stages[${i}][is_won]" value="${stage.is_won ? '1' : '0'}">
            <label><input type="radio" name="lost_selector" ${stage.is_lost ? 'checked' : ''}> Lost</label>
            <input type="hidden" name="pipeline_stages[${i}][is_lost]" value="${stage.is_lost ? '1' : '0'}">
            <button type="button" class="remove-row" title="Remove">✕</button>
        `;
        row.querySelector('input[name="won_selector"]').addEventListener('change', () => {
            document.querySelectorAll('input[name="won_selector"]').forEach(r => {
                r.closest('.repeat-row').querySelector('input[type=hidden][name$="[is_won]"]').value = r.checked ? '1' : '0';
            });
        });
        row.querySelector('input[name="lost_selector"]').addEventListener('change', () => {
            document.querySelectorAll('input[name="lost_selector"]').forEach(r => {
                r.closest('.repeat-row').querySelector('input[type=hidden][name$="[is_lost]"]').value = r.checked ? '1' : '0';
            });
        });
        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        stageRows.appendChild(row);
    }

    document.getElementById('addStageRow').addEventListener('click', () => addStageRow());
    @foreach (old('pipeline_stages', $settings->pipeline_stages ?? []) as $stage)
        addStageRow(@json($stage));
    @endforeach

    let sourceIndex = 0;
    const sourceRows = document.getElementById('sourceRows');

    function addSourceRow(value) {
        const i = sourceIndex++;
        const row = document.createElement('div');
        row.className = 'repeat-row';
        row.innerHTML = `
            <input type="text" name="lead_sources[${i}]" value="${value || ''}" placeholder="e.g. Website">
            <button type="button" class="remove-row" title="Remove">✕</button>
        `;
        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        sourceRows.appendChild(row);
    }

    document.getElementById('addSourceRow').addEventListener('click', () => addSourceRow());
    @foreach (old('lead_sources', $settings->lead_sources ?? []) as $source)
        addSourceRow(@json($source));
    @endforeach
    if (sourceIndex === 0) addSourceRow();
</script>
@endsection
