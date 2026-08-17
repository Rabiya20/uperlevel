@php
    $s = fn ($field, $default = '') => old($field, $employee->{$field} ?? $default);
    $sDate = fn ($field) => old($field, optional($employee->{$field} ?? null)->format('Y-m-d'));
@endphp

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Basic Details</h3></div>
            <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="f-label">Full name</label>
                    <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="120" required>
                </div>
                <div>
                    <label class="f-label">Email</label>
                    <input class="f-input" type="email" name="email" value="{{ $s('email') }}" maxlength="190" required>
                </div>
                <div>
                    <label class="f-label">Phone</label>
                    <input class="f-input" type="text" name="phone" value="{{ $s('phone') }}" maxlength="30">
                </div>
                <div>
                    <label class="f-label">Employee code</label>
                    <input class="f-input" type="text" name="employee_code" value="{{ $s('employee_code', $suggestedCode ?? '') }}" maxlength="30" placeholder="e.g. EMP-101">
                    <p class="f-hint">Suggested automatically from HR Setup's prefix — feel free to change it.</p>
                </div>
                <div>
                    <label class="f-label">Gender</label>
                    <select class="f-input" name="gender">
                        <option value="">—</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected($s('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Date of birth</label>
                    <input class="f-input" type="date" name="date_of_birth" value="{{ $sDate('date_of_birth') }}">
                </div>
                <div>
                    <label class="f-label">CNIC / National ID</label>
                    <input class="f-input" type="text" name="cnic" value="{{ $s('cnic') }}" maxlength="30">
                </div>
                <div style="grid-column:1 / -1;">
                    <label class="f-label">Address</label>
                    <textarea class="f-input" name="address" rows="2" maxlength="1000">{{ $s('address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Emergency Contact</h3></div>
            <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="f-label">Name</label>
                    <input class="f-input" type="text" name="emergency_contact_name" value="{{ $s('emergency_contact_name') }}" maxlength="120">
                </div>
                <div>
                    <label class="f-label">Phone</label>
                    <input class="f-input" type="text" name="emergency_contact_phone" value="{{ $s('emergency_contact_phone') }}" maxlength="30">
                </div>
            </div>
        </div>

    </div>

    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Employment</h3></div>
            <div style="padding:18px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="f-label">Role</label>
                    <select class="f-input" name="role" required>
                        <option value="employee" @selected($s('role', 'employee') === 'employee')>Employee</option>
                        <option value="manager" @selected($s('role') === 'manager')>Manager</option>
                    </select>
                </div>
                <div>
                    <label class="f-label">Access role</label>
                    <select class="f-input" name="role_id">
                        <option value="">— (base role only)</option>
                        @foreach ($roles as $accessRole)
                            <option value="{{ $accessRole->id }}" @selected((int) $s('role_id') === $accessRole->id)>{{ $accessRole->name }} ({{ $accessRole->portal === 'admin' ? 'Admin' : 'Employee' }} Portal)</option>
                        @endforeach
                    </select>
                    <!-- <p class="f-hint">Fine-grained module permissions from Company → User Role. Must match this person's portal (Manager = Admin Portal, Employee = Employee Portal).</p> -->
                </div>
                <div>
                    <label class="f-label">Designation</label>
                    <select class="f-input" name="designation_id">
                        <option value="">—</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}" @selected((int) $s('designation_id') === $designation->id)>{{ $designation->name }}{{ $designation->department ? ' ('.$designation->department->name.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Department</label>
                    <select class="f-input" name="department_id">
                        <option value="">—</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $s('department_id') === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Employment type</label>
                    <select class="f-input" name="employment_type">
                        @foreach (\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($s('employment_type', 'full_time') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Status</label>
                    <select class="f-input" name="employment_status">
                        @foreach (\App\Models\User::EMPLOYMENT_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($s('employment_status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Date of joining</label>
                    <input class="f-input" type="date" name="date_of_joining" value="{{ $sDate('date_of_joining') }}">
                </div>
                <div>
                    <label class="f-label">Shift</label>
                    <select class="f-input" name="shift_id" id="shiftSelect">
                        <option value="">No shift</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((int) $s('shift_id') === $shift->id)>{{ $shift->name }} ({{ $shift->formattedRange() }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1 / -1;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label class="f-label">Custom start time — optional</label>
                        <input class="f-input" type="time" name="custom_start_time" id="customStartTime" value="{{ $s('custom_start_time') }}">
                    </div>
                    <div>
                        <label class="f-label">Custom end time — optional</label>
                        <input class="f-input" type="time" name="custom_end_time" id="customEndTime" value="{{ $s('custom_end_time') }}">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <p class="f-hint">Overrides the shift's standard hours for just this employee — e.g. Evening Shift but working 6 PM–1 AM instead. Leave both blank to use the shift's own hours. Fill in both, not just one.</p>
                    </div>
                </div>
                <div>
                    <label class="f-label">Reports to</label>
                    <select class="f-input" name="reporting_manager_id">
                        <option value="">—</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected((int) $s('reporting_manager_id') === $manager->id)>{{ $manager->name }} ({{ ucfirst($manager->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f-label">Basic salary</label>
                    <input class="f-input" type="number" step="0.01" min="0" name="basic_salary" value="{{ $s('basic_salary') }}">
                </div>
                <div style="grid-column:1 / -1;">
                    <label class="f-label">Workstation machine name — optional</label>
                    <input class="f-input" type="text" name="machine_name" value="{{ $s('machine_name') }}" placeholder="e.g. OFFICE-PC-01" maxlength="100" autocomplete="off">
                    <p class="f-hint">Matches this employee to their desktop computer for the screen monitoring agent (Company → User Working). Find it on Windows via <code>hostname</code> in Command Prompt, or Settings → System → About.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Employee</button>
</div>

<script>
    (function () {
        var shiftSelect = document.getElementById('shiftSelect');
        var customStart = document.getElementById('customStartTime');
        var customEnd = document.getElementById('customEndTime');

        function toggle() {
            var hasShift = shiftSelect.value !== '';
            [customStart, customEnd].forEach(function (input) {
                input.disabled = ! hasShift;
                if (! hasShift) {
                    input.value = '';
                }
            });
        }

        shiftSelect.addEventListener('change', toggle);
        toggle();
    })();
</script>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-input:disabled{background:var(--bg);color:var(--ink-soft);cursor:not-allowed;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
</style>
