@extends('layouts.admin')

@section('title', 'HR Setup — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>HR Setup</h2>
        <p>Shifts, overtime rules and working days that every attendance record is measured against.</p>
    </div>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

<div class="setup-shell">
    <div class="setup-nav">
        @foreach (['general' => 'General', 'shifts' => 'Shifts', 'leave' => 'Leave', 'departments' => 'Departments', 'overtime' => 'Overtime', 'payroll' => 'Payroll', 'payroll-lock' => 'Payroll Lock'] as $key => $label)
            <button type="button" class="setup-nav-btn{{ $loop->first ? ' active' : '' }}" data-category="{{ $key }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="setup-panels">
        <form method="POST" action="{{ route('admin.hr.settings.update') }}">
            @csrf
            @method('PUT')

            {{-- General --}}
            <div class="setup-cat" data-category="general">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Employee ID</h3></div>
                    <div style="padding:18px 20px;">
                        <label class="f-label">Employee code prefix</label>
                        <input class="f-input" type="text" name="employee_code_prefix" value="{{ old('employee_code_prefix', $settings->employee_code_prefix) }}" maxlength="20" placeholder="e.g. EMP-">
                        <p class="f-hint">New employees are suggested the next code after this prefix (next up: "{{ $nextEmployeeCode }}") — still editable per employee, and never allowed to duplicate.</p>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3>Working Days</h3></div>
                    <div style="padding:16px 20px;display:flex;flex-wrap:wrap;gap:14px;">
                        @php
                            $days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                            $selected = old('working_days', $settings->working_days ?? []);
                        @endphp
                        @foreach ($days as $value => $label)
                            <label class="f-check">
                                <input type="checkbox" name="working_days[]" value="{{ $value }}" @checked(in_array($value, $selected))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="f-hint" style="padding:0 20px 16px;margin:0;">Non-working days never count as "absent" on the attendance calendar and reports.</p>
                </div>
            </div>

            {{-- Shifts --}}
            <div class="setup-cat" data-category="shifts" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Shifts</h3></div>
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <p class="f-hint" style="margin:0;">{{ $shiftCount }} shift{{ $shiftCount === 1 ? '' : 's' }} configured — each employee is assigned one from the Attendance page.</p>
                        <a href="{{ route('admin.hr.shifts.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                    </div>
                </div>
            </div>

            {{-- Leave --}}
            <div class="setup-cat" data-category="leave" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Leave Types</h3></div>
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <p class="f-hint" style="margin:0;">{{ $leaveTypeCount }} leave type{{ $leaveTypeCount === 1 ? '' : 's' }} configured — each defines days/year, caps and whether unused days carry forward.</p>
                        <a href="{{ route('admin.hr.leave-types.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                    </div>
                </div>
            </div>

            {{-- Departments --}}
            <div class="setup-cat" data-category="departments" style="display:none;">
                <div class="panel" style="margin-bottom:18px;">
                    <div class="panel-head"><h3>Departments</h3></div>
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <p class="f-hint" style="margin:0;">{{ $departmentCount }} department{{ $departmentCount === 1 ? '' : 's' }} configured — used as a dropdown on every employee's profile.</p>
                        <a href="{{ route('admin.hr.departments.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3>Designations</h3></div>
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <p class="f-hint" style="margin:0;">{{ $designationCount }} designation{{ $designationCount === 1 ? '' : 's' }} configured — used as a dropdown on every employee's profile.</p>
                        <a href="{{ route('admin.hr.designations.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                    </div>
                </div>
            </div>

            {{-- Overtime --}}
            <div class="setup-cat" data-category="overtime" style="display:none;">
                <div class="panel">
                    <div class="panel-head"><h3>Overtime</h3></div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                        <label class="f-check">
                            <input type="hidden" name="overtime_enabled" value="0">
                            <input type="checkbox" name="overtime_enabled" value="1" @checked(old('overtime_enabled', $settings->overtime_enabled))>
                            Track overtime for shift-assigned employees
                        </label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div>
                                <label class="f-label">Overtime multiplier</label>
                                <input class="f-input" type="number" step="0.1" min="1" max="5" name="overtime_multiplier" value="{{ old('overtime_multiplier', $settings->overtime_multiplier) }}">
                                <p class="f-hint">Informational for now — no payroll module to apply it to yet.</p>
                            </div>
                            <div>
                                <label class="f-label">Grace before OT counts (minutes)</label>
                                <input class="f-input" type="number" min="0" max="120" name="overtime_threshold_minutes" value="{{ old('overtime_threshold_minutes', $settings->overtime_threshold_minutes) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payroll --}}
            <div class="setup-cat" data-category="payroll" style="display:none;">
                @if (auth()->user()->isOwnerOrAdmin() || auth()->user()->isSuperAdmin())
                    <div class="panel" style="margin-bottom:18px;">
                        <div class="panel-head"><h3>Payroll Structure</h3></div>
                        <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                            <p class="f-hint" style="margin:0;">{{ $payrollComponentCount }} pay component{{ $payrollComponentCount === 1 ? '' : 's' }} configured — e.g. Basic (payable), Tax (deductible). Assign amounts per employee from their profile.</p>
                            <a href="{{ route('admin.hr.payroll-components.index') }}" class="btn btn-ghost" style="white-space:nowrap;padding:8px 14px;font-size:12.5px;">Manage →</a>
                        </div>
                    </div>
                @endif

                <div class="panel">
                    <div class="panel-head"><h3>Payroll</h3></div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Payroll cycle</label>
                            <select class="f-input" name="payroll_cycle">
                                @foreach (['weekly' => 'Weekly', 'biweekly' => 'Bi-weekly', 'monthly' => 'Monthly'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payroll_cycle', $settings->payroll_cycle) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="f-label">Pay day (day of month)</label>
                            <select class="f-input" name="payroll_pay_day">
                                @foreach (range(1, 28) as $day)
                                    <option value="{{ $day }}" @selected((int) old('payroll_pay_day', $settings->payroll_pay_day) === $day)>{{ $day }}</option>
                                @endforeach
                            </select>
                            <p class="f-hint">Capped at 28 so it falls in every month, including February.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Save HR Settings</button>
            </div>
        </form>

        {{-- Payroll Lock — its own standalone forms (lock/unlock are immediate
             actions, not part of the batched settings save above; kept outside
             that <form> since a form can't be validly nested inside another). --}}
        <div class="setup-cat" data-category="payroll-lock" style="display:none;">
            <div class="panel">
                <div class="panel-head"><h3>Lock Payroll</h3></div>
                <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                    <p class="f-hint" style="margin:0;line-height:1.6;">
                        Once you've run payroll for a period, lock it through that date. After locking:
                        employees can't submit new leave requests for dates on or before it, and you can't
                        approve pending requests for those dates either — so nothing about attendance or
                        leave for an already-paid period can quietly change afterwards.
                        <br><br>
                        Example: payroll runs through 31 July → lock through <strong>31 Jul</strong>. Need to
                        fix something for July after that? Unlock, make the correction, then re-lock.
                    </p>
                </div>
            </div>

            <div class="panel" style="margin-top:18px;">
                <div class="panel-head"><h3>Payroll Lock Status</h3></div>
                <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                    @if ($settings->payroll_locked_through)
                        <div style="background:#FFF4E5;border-radius:8px;padding:12px;font-size:12.5px;color:#B4690E;">
                            Payroll is locked through <strong>{{ $settings->payroll_locked_through->format('M j, Y') }}</strong> — leave requests touching this period are blocked.
                        </div>
                        <form method="POST" action="{{ route('admin.hr.settings.payroll-unlock') }}" style="align-self:flex-start;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost">Unlock Payroll</button>
                        </form>
                    @else
                        <p class="f-hint" style="margin:0;">Payroll is currently unlocked — leave requests can be submitted and approved for any date.</p>
                        <form method="POST" action="{{ route('admin.hr.settings.payroll-lock') }}" style="display:flex;gap:10px;align-items:flex-end;">
                            @csrf
                            <div>
                                <label class="f-label">Lock through</label>
                                <input class="f-input" type="date" name="payroll_locked_through" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Lock Payroll</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:8px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}

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
</script>
@endsection
