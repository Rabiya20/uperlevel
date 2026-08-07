<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Present Days</div>
        <div class="kpi-value">{{ $stats['present'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Late Days</div>
        <div class="kpi-value">{{ $stats['late'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Absent Days</div>
        <div class="kpi-value">{{ $stats['absent'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Leave Days</div>
        <div class="kpi-value">{{ $stats['leave'] }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Worked Hours</div>
        <div class="kpi-value">{{ $stats['worked_hours'] }}</div>
    </div>
    @if ($settings->overtime_enabled)
        <div class="kpi-card">
            <div class="kpi-label">Overtime Hours</div>
            <div class="kpi-value">{{ $stats['overtime_hours'] }}</div>
        </div>
    @endif
</div>
