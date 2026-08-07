<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'leave_type_id', 'start_date', 'end_date',
        'days', 'reason', 'status', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decided_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Working-day count between two dates (inclusive), per HrSettings::working_days. */
    public static function computeDays(HrSettings $settings, Carbon $start, Carbon $end): int
    {
        $days = 0;

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            if ($settings->isWorkingDay($cursor)) {
                $days++;
            }
        }

        return $days;
    }
}
