@extends('layouts.admin')

@section('title', 'Attendance Import — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Attendance Import</h2>
        <p><a href="{{ route('admin.hr.attendance.index') }}" style="color:var(--primary-dark);">← Back to Attendance</a> — backfill previous attendance records. Today onwards is marked automatically on login.</p>
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h3>Add attendance manually</h3></div>
        <form method="POST" action="{{ route('admin.hr.attendance.manual.store') }}" style="padding:18px 20px;" id="manualForm">
            @csrf
            <div style="margin-bottom:14px;">
                <label class="f-label">Employee</label>
                <select class="f-input" name="user_id" required>
                    <option value="">— Select —</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('user_id', $selectedUserId) == $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label class="f-label">Date</label>
                <input class="f-input" type="date" name="work_date" max="{{ $maxDate }}" value="{{ old('work_date', $selectedDate) }}" required>
                <p class="f-hint">Must be a previous day — today's attendance is marked automatically on login.</p>
            </div>
            <div style="margin-bottom:14px;">
                <label class="f-label">Status</label>
                <select class="f-input" name="status" id="statusSelect" required>
                    <option value="present" @selected(old('status', 'present') === 'present')>Present</option>
                    <option value="absent" @selected(old('status') === 'absent')>Absent</option>
                </select>
            </div>
            <div id="timeFields">
                <div style="margin-bottom:14px;">
                    <label class="f-label">Check-in time</label>
                    <select class="f-input" name="check_in_time" id="checkInSelect">
                        <option value="">— Select —</option>
                        @foreach ($timeOptions as $t)
                            <option value="{{ $t['value'] }}" @selected(old('check_in_time') === $t['value'])>{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:14px;">
                    <label class="f-label">Check-out time — optional</label>
                    <select class="f-input" name="check_out_time">
                        <option value="">— Select —</option>
                        @foreach ($timeOptions as $t)
                            <option value="{{ $t['value'] }}" @selected(old('check_out_time') === $t['value'])>{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="f-hint">Leave blank if the employee forgot to check out — it'll be filled in automatically once their shift ends.</p>
                </div>
            </div>
            <div style="text-align:right;">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-head"><h3>Import from Excel</h3></div>
        <div style="padding:18px 20px;">
            <p style="font-size:13px;color:var(--ink-soft);margin:0 0 14px;">
                Bringing in attendance history from another system? Download the template below, fill it in, and upload it here.
                <strong>Employee Email</strong>, <strong>Date</strong> and <strong>Status</strong> are required; Check-in Time is required for a Present row, Check-out Time is optional.
                Re-uploading the same Employee + Date corrects that row rather than duplicating it.
            </p>

            <a href="{{ route('admin.hr.attendance.import.template') }}" class="btn btn-ghost" style="margin-bottom:18px;">⬇ Download sample template</a>

            <form method="POST" action="{{ route('admin.hr.attendance.import.store') }}" enctype="multipart/form-data">
                @csrf
                <label class="f-label">Spreadsheet file (.xlsx, .xls or .csv — max 5MB)</label>
                <input class="f-input" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                <div style="margin-top:16px;text-align:right;">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
</style>

<script>
    (function () {
        var statusSelect = document.getElementById('statusSelect');
        var timeFields = document.getElementById('timeFields');
        var checkInSelect = document.getElementById('checkInSelect');

        function toggle() {
            var isPresent = statusSelect.value === 'present';
            timeFields.style.display = isPresent ? '' : 'none';
            checkInSelect.required = isPresent;
        }

        statusSelect.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endsection
