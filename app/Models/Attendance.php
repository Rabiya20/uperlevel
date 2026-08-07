<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Attendance extends Model
{
    public const MAX_IMPORT_ROWS = 1000;

    private const IMPORT_FIELD_ALIASES = [
        'email' => ['employee email', 'email'],
        'date' => ['date', 'work date'],
        'status' => ['status'],
        'check_in_time' => ['check-in time', 'check in time', 'check in'],
        'check_out_time' => ['check-out time', 'check out time', 'check out'],
    ];

    protected $fillable = ['user_id', 'tenant_id', 'shift_id', 'work_date', 'check_in', 'check_out', 'status', 'marked_by', 'marked_at'];

    protected $casts = [
        'work_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'marked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Automatically check the user in for today if they have not been
     * checked in already. Called from the login flow. Stamps whichever
     * shift the user is currently assigned to, so a later reassignment
     * never rewrites this day's history.
     */
    public static function autoCheckIn(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => now()->toDateString()],
            ['tenant_id' => $user->tenant_id, 'shift_id' => $user->shift_id, 'check_in' => now()]
        );
    }

    public static function checkOutToday(User $user): void
    {
        static::where('user_id', $user->id)
            ->where('work_date', now()->toDateString())
            ->update(['check_out' => now()]);
    }

    /**
     * A day only counts as absent-eligible once it's actually over — a past
     * calendar day always qualifies; today only qualifies once the user's
     * shift has ended (no shift assigned means "unknown", so today never
     * qualifies until it's already yesterday).
     */
    public static function isEligibleForAbsent(Carbon $date, User $user): bool
    {
        $today = now()->startOfDay();

        if ($date->copy()->startOfDay()->lt($today)) {
            return true;
        }

        if ($date->copy()->startOfDay()->gt($today)) {
            return false;
        }

        return $user->shift && now()->greaterThan($user->shift->endDateTimeFor($date));
    }

    /** Null when there's no shift to compare against, or no check-in yet. */
    public function isLate(): ?bool
    {
        if (! $this->check_in || ! $this->shift) {
            return null;
        }

        $expectedStart = $this->shift->startDateTimeFor($this->work_date)->addMinutes($this->shift->grace_minutes);

        return $this->check_in->greaterThan($expectedStart);
    }

    /**
     * Minutes actually worked. Uses "now" for a still-open session on
     * today's date; a past day with a check-in but no check-out returns
     * null rather than guessing. Break minutes are subtracted when a
     * shift is set.
     */
    public function workedMinutes(): ?int
    {
        if (! $this->check_in) {
            return null;
        }

        $end = $this->check_out ?? ($this->work_date->isToday() ? now() : null);
        if (! $end) {
            return null;
        }

        $minutes = $this->check_in->diffInMinutes($end);

        if ($this->shift) {
            $minutes = max(0, $minutes - $this->shift->break_minutes);
        }

        return $minutes;
    }

    /** 0 unless overtime is enabled tenant-wide, a shift is set, and the day is checked out. */
    public function overtimeMinutes(HrSettings $settings): int
    {
        if (! $settings->overtime_enabled || ! $this->shift || ! $this->check_out) {
            return 0;
        }

        $expectedEnd = $this->shift->endDateTimeFor($this->work_date)->addMinutes($settings->overtime_threshold_minutes);

        return $this->check_out->greaterThan($expectedEnd) ? $expectedEnd->diffInMinutes($this->check_out) : 0;
    }

    /**
     * Classifies every day in the range as present/late/absent/leave/holiday/
     * day_off (or null — before joining, or a future day not yet due) — the
     * single source of truth the calendar, the stats strip, and the reports
     * all read from, so they can never disagree with each other. Keyed by
     * "Y-m-d". Each entry:
     *   type: 'present'|'late'|'absent'|'leave'|'holiday'|'day_off'|null
     *   label: leave type name, holiday name, or null
     *   attendance: the Attendance row, if any
     *   persisted: whether an 'absent' entry is a saved record vs. only
     *              inferred live from the day being over with no check-in
     */
    public static function dayStatusesFor(User $user, Carbon $start, Carbon $end, HrSettings $settings): Collection
    {
        $attendanceByDate = static::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('shift')
            ->get()
            ->keyBy(fn (self $a) => $a->work_date->format('Y-m-d'));

        $leaveLabelByDate = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->with('leaveType')
            ->get()
            ->reduce(function (Collection $carry, LeaveRequest $leave) {
                for ($d = $leave->start_date->copy(); $d->lessThanOrEqualTo($leave->end_date); $d->addDay()) {
                    $carry->put($d->format('Y-m-d'), $leave->leaveType->name);
                }

                return $carry;
            }, collect());

        $holidayNameByDate = Holiday::where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->format('Y-m-d'))
            ->map(fn (Holiday $h) => $h->name);

        $joinedAt = $user->date_of_joining?->copy()->startOfDay();
        $statuses = collect();

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $key = $cursor->format('Y-m-d');
            $attendance = $attendanceByDate->get($key);

            if ($attendance && $attendance->check_in) {
                $statuses->put($key, [
                    'type' => $attendance->isLate() ? 'late' : 'present',
                    'label' => null, 'attendance' => $attendance, 'persisted' => true,
                ]);

                continue;
            }

            if ($attendance && $attendance->status === 'absent') {
                $statuses->put($key, ['type' => 'absent', 'label' => null, 'attendance' => $attendance, 'persisted' => true]);

                continue;
            }

            if ($holidayNameByDate->has($key)) {
                $statuses->put($key, ['type' => 'holiday', 'label' => $holidayNameByDate[$key], 'attendance' => null, 'persisted' => true]);

                continue;
            }

            if ($leaveLabelByDate->has($key)) {
                $statuses->put($key, ['type' => 'leave', 'label' => $leaveLabelByDate[$key], 'attendance' => null, 'persisted' => true]);

                continue;
            }

            if (! $settings->isWorkingDay($cursor)) {
                $statuses->put($key, ['type' => 'day_off', 'label' => null, 'attendance' => null, 'persisted' => true]);

                continue;
            }

            if ($joinedAt && $cursor->copy()->startOfDay()->lt($joinedAt)) {
                $statuses->put($key, ['type' => null, 'label' => null, 'attendance' => null, 'persisted' => false]);

                continue;
            }

            $eligible = self::isEligibleForAbsent($cursor, $user);
            $statuses->put($key, [
                'type' => $eligible ? 'absent' : null, 'label' => null, 'attendance' => null, 'persisted' => false,
            ]);
        }

        return $statuses;
    }

    /**
     * Present/late/absent/leave/holiday day counts + total worked/overtime
     * hours across a date range, derived from dayStatusesFor() so the
     * numbers always agree with what the calendar shows for the same range.
     *
     * @param  Collection<string, array>  $dayStatuses
     */
    public static function summarize(Collection $dayStatuses, HrSettings $settings): array
    {
        $present = $late = $absent = $leave = $holiday = 0;
        $workedMinutes = $overtimeMinutes = 0;

        foreach ($dayStatuses as $entry) {
            switch ($entry['type']) {
                case 'present':
                    $present++;
                    $workedMinutes += $entry['attendance']->workedMinutes() ?? 0;
                    $overtimeMinutes += $entry['attendance']->overtimeMinutes($settings);
                    break;
                case 'late':
                    $present++;
                    $late++;
                    $workedMinutes += $entry['attendance']->workedMinutes() ?? 0;
                    $overtimeMinutes += $entry['attendance']->overtimeMinutes($settings);
                    break;
                case 'absent':
                    $absent++;
                    break;
                case 'leave':
                    $leave++;
                    break;
                case 'holiday':
                    $holiday++;
                    break;
            }
        }

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'leave' => $leave,
            'holiday' => $holiday,
            'worked_hours' => round($workedMinutes / 60, 1),
            'overtime_hours' => round($overtimeMinutes / 60, 1),
        ];
    }

    // ---- Backfill (manual entry + Excel import) --------------------------
    // Both entry points funnel through here so a manually-typed day and an
    // imported row behave identically — same upsert, same audit trail.

    /**
     * Creates or corrects a single day's record for a date that has already
     * passed. Check-out is optional even for a present day — a day left
     * without one is exactly the "forgot to checkout" case, resolved later
     * by the attendance:auto-checkout scheduled command rather than here.
     */
    public static function upsertManualEntry(User $user, Carbon $date, string $status, ?string $checkInTime, ?string $checkOutTime, User $markedBy): self
    {
        $checkIn = $status === 'present' && $checkInTime ? Carbon::parse($date->toDateString().' '.$checkInTime) : null;
        $checkOut = $status === 'present' && $checkOutTime ? Carbon::parse($date->toDateString().' '.$checkOutTime) : null;

        $attendance = static::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $date->toDateString()],
            [
                'tenant_id' => $user->tenant_id,
                'shift_id' => $user->shift_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status === 'absent' ? 'absent' : null,
                'marked_by' => $markedBy->id,
                'marked_at' => now(),
            ]
        );

        ActivityLog::record('attendance_backfilled', "Set {$date->toDateString()} attendance for {$user->name} to {$status}.", $user, ['date' => $date->toDateString(), 'status' => $status]);

        return $attendance;
    }

    /** @return array{created: array, updated: array, errors: array, total: int} */
    public static function importFromSpreadsheet(UploadedFile $file, Tenant $tenant, User $markedBy): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not read this file — make sure it\'s a valid .xlsx, .xls or .csv export.');
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        if (empty($rows)) {
            throw new \RuntimeException('That file is empty.');
        }

        $header = array_shift($rows);
        $columnForField = [];
        foreach ($header as $i => $cell) {
            $normalized = strtolower(trim((string) $cell));
            foreach (self::IMPORT_FIELD_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $columnForField[$field] = $i;
                    break;
                }
            }
        }

        if (! isset($columnForField['email']) || ! isset($columnForField['date']) || ! isset($columnForField['status'])) {
            throw new \RuntimeException('Couldn\'t find "Employee Email", "Date" and "Status" columns — download the sample template and match its headers.');
        }

        $rows = array_slice($rows, 0, self::MAX_IMPORT_ROWS);

        $created = [];
        $updated = [];
        $errors = [];
        $rowNumber = 1; // row 1 was the header

        foreach ($rows as $raw) {
            $rowNumber++;

            $data = [];
            foreach ($columnForField as $field => $colIndex) {
                $value = $raw[$colIndex] ?? null;
                $value = is_string($value) ? trim($value) : $value;
                $data[$field] = $value === '' ? null : $value;
            }

            if (empty(array_filter($data, fn ($v) => $v !== null))) {
                continue; // silently skip fully-blank rows
            }

            $result = self::evaluateImportRow($data, $tenant->id);

            if ($result['error']) {
                $errors[] = ['row' => $rowNumber, 'email' => $data['email'] ?? '—', 'message' => $result['error']];

                continue;
            }

            ['user' => $user, 'date' => $date, 'status' => $status, 'check_in_time' => $checkInTime, 'check_out_time' => $checkOutTime] = $result['data'];

            $attendance = self::upsertManualEntry($user, $date, $status, $checkInTime, $checkOutTime, $markedBy);
            $entry = ['row' => $rowNumber, 'user' => $user, 'date' => $date->toDateString(), 'status' => $status];

            $attendance->wasRecentlyCreated ? $created[] = $entry : $updated[] = $entry;
        }

        ActivityLog::record('attendance_imported', 'Imported attendance from spreadsheet — '.count($created).' new, '.count($updated).' updated, '.count($errors).' skipped.');

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors, 'total' => count($rows)];
    }

    /** @return array{error: ?string, data: array} */
    private static function evaluateImportRow(array $data, int $tenantId): array
    {
        $fail = fn (string $message) => ['error' => $message, 'data' => []];

        if (blank($data['email'] ?? null) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $fail('A valid employee email is required.');
        }

        $user = User::where('tenant_id', $tenantId)->where('email', mb_strtolower($data['email']))->first();
        if (! $user) {
            return $fail('No employee found with this email.');
        }

        if (blank($data['date'] ?? null)) {
            return $fail('Date is required.');
        }
        try {
            $date = Carbon::parse($data['date'])->startOfDay();
        } catch (\Throwable $e) {
            return $fail('Date isn\'t valid.');
        }
        if (! $date->lt(now()->startOfDay())) {
            return $fail('Date must be in the past — this tool is for backfilling previous records only.');
        }

        $status = strtolower((string) ($data['status'] ?? ''));
        if (! in_array($status, ['present', 'absent'], true)) {
            return $fail('Status must be "Present" or "Absent".');
        }

        $checkInTime = null;
        $checkOutTime = null;
        if ($status === 'present') {
            if (blank($data['check_in_time'] ?? null)) {
                return $fail('Check-in Time is required for a Present day.');
            }
            try {
                $checkInTime = Carbon::parse($data['check_in_time'])->format('H:i');
            } catch (\Throwable $e) {
                return $fail('Check-in Time isn\'t a valid time.');
            }
            if (filled($data['check_out_time'] ?? null)) {
                try {
                    $checkOutTime = Carbon::parse($data['check_out_time'])->format('H:i');
                } catch (\Throwable $e) {
                    return $fail('Check-out Time isn\'t a valid time.');
                }
            }
        }

        return [
            'error' => null,
            'data' => ['user' => $user, 'date' => $date, 'status' => $status, 'check_in_time' => $checkInTime, 'check_out_time' => $checkOutTime],
        ];
    }

    /** The downloadable "fill this in" template — single source of truth for the column contract. */
    public static function sampleImportSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        $headers = ['Employee Email', 'Date', 'Status', 'Check-in Time', 'Check-out Time'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $sheet->fromArray(['jane@example.com', now()->subDay()->toDateString(), 'Present', '09:00 AM', '06:00 PM'], null, 'A2');
        $sheet->fromArray(['john@example.com', now()->subDay()->toDateString(), 'Absent', '', ''], null, 'A3');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
