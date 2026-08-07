@php
    $prevMonth = $start->copy()->subMonth();
    $nextMonth = $start->copy()->addMonth();
    $isAdmin = $isAdmin ?? false;
    $today = now()->startOfDay();
@endphp

<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>{{ $start->format('F Y') }}</h3>
        <div style="display:flex;gap:8px;">
            <a href="{{ route($calendarRoute, array_merge($calendarRouteParams, ['month' => $prevMonth->format('Y-m')])) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">← Prev</a>
            <a href="{{ route($calendarRoute, array_merge($calendarRouteParams, ['month' => now()->format('Y-m')])) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Today</a>
            <a href="{{ route($calendarRoute, array_merge($calendarRouteParams, ['month' => $nextMonth->format('Y-m')])) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Next →</a>
        </div>
    </div>
    <div style="padding:16px 20px;">
        <div class="cal-grid cal-head">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                <div class="cal-head-cell">{{ $d }}</div>
            @endforeach
        </div>
        <div class="cal-grid">
            @for ($i = 0; $i < $start->dayOfWeek; $i++)
                <div class="cal-cell cal-empty"></div>
            @endfor
            @for ($day = 1; $day <= $end->day; $day++)
                @php
                    $date = $start->copy()->day($day);
                    $key = $date->format('Y-m-d');
                    $entry = $dayStatuses->get($key);
                    $type = $entry['type'] ?? null;
                    $att = $entry['attendance'] ?? null;
                @endphp
                <div class="cal-cell {{ $date->isToday() ? 'cal-today' : '' }}">
                    <div class="cal-day-num">{{ $day }}</div>

                    @if ($type === 'present' || $type === 'late')
                        <span class="cal-dot {{ $type === 'late' ? 'cal-dot-late' : 'cal-dot-present' }}"></span>
                        <div class="cal-time">{{ $currentTenant->localizeTime($att->check_in)->format('g:i A') }}</div>
                        @if ($type === 'late')
                            <div class="cal-tag" style="color:#B4690E;">Late</div>
                        @endif
                    @elseif ($type === 'leave')
                        <span class="cal-dot cal-dot-leave"></span>
                        <div class="cal-tag" style="color:#6C4FD6;">{{ $entry['label'] }}</div>
                    @elseif ($type === 'holiday')
                        <span class="cal-dot cal-dot-holiday"></span>
                        <div class="cal-tag" style="color:#0E7C9C;">{{ $entry['label'] }}</div>
                    @elseif ($type === 'day_off')
                        <span class="cal-dot cal-dot-weekend"></span>
                        <div class="cal-tag">Day Off</div>
                    @elseif ($type === 'absent')
                        <span class="cal-dot cal-dot-absent"></span>
                        <div class="cal-tag" style="color:#C0392B;">Absent</div>
                    @endif

                    @if ($isAdmin && in_array($type, ['present', 'late', 'absent'], true) && $date->lt($today))
                        <a href="{{ route('admin.hr.attendance.import.index', ['user_id' => $attendanceUserId, 'date' => $key]) }}" class="cal-mark-btn" style="display:inline-block;margin-top:2px;" title="Correct this day's attendance">Edit</a>
                    @endif
                </div>
            @endfor
        </div>

        <div style="display:flex;gap:14px;margin-top:14px;font-size:11.5px;color:var(--ink-soft);flex-wrap:wrap;">
            <span><span class="cal-dot cal-dot-present" style="vertical-align:middle;"></span> Present</span>
            <span><span class="cal-dot cal-dot-late" style="vertical-align:middle;"></span> Late</span>
            <span><span class="cal-dot cal-dot-absent" style="vertical-align:middle;"></span> Absent</span>
            <span><span class="cal-dot cal-dot-leave" style="vertical-align:middle;"></span> Leave</span>
            <span><span class="cal-dot cal-dot-holiday" style="vertical-align:middle;"></span> Holiday</span>
            <span><span class="cal-dot cal-dot-weekend" style="vertical-align:middle;"></span> Day Off</span>
        </div>
    </div>
</div>

<style>
    .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;}
    .cal-head{margin-bottom:6px;}
    .cal-head-cell{font-size:11px;font-weight:700;color:var(--ink-soft);text-align:center;text-transform:uppercase;}
    .cal-cell{min-height:64px;border:1px solid var(--line);border-radius:8px;padding:6px;background:var(--bg);}
    .cal-cell.cal-empty{border:none;background:transparent;}
    .cal-cell.cal-today{border-color:var(--primary);border-width:2px;}
    .cal-day-num{font-size:12px;font-weight:700;color:var(--ink);}
    .cal-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-top:4px;}
    .cal-dot-present{background:#0F7C50;}
    .cal-dot-late{background:#B4690E;}
    .cal-dot-absent{background:#C0392B;}
    .cal-dot-leave{background:#6C4FD6;}
    .cal-dot-holiday{background:#0E7C9C;}
    .cal-dot-weekend{background:var(--ink-soft);opacity:.4;}
    .cal-time{font-size:10px;color:var(--ink-soft);margin-top:2px;}
    .cal-tag{font-size:10px;font-weight:700;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .cal-mark-btn{border:none;background:none;padding:0;font-size:9.5px;font-weight:700;color:var(--primary-dark);cursor:pointer;text-decoration:underline;}
</style>
