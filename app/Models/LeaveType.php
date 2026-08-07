<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'days_per_year', 'max_per_month', 'carry_forward',
        'max_carry_forward_days', 'max_accumulation_days', 'is_encashable', 'is_active',
    ];

    protected $casts = [
        'carry_forward' => 'boolean',
        'is_encashable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Approved days used by this user, for this type, in the given calendar year. */
    public function usedDays(User $user, int $year): int
    {
        return (int) $this->leaveRequests()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    /** Approved days used by this user, for this type, in the given calendar month. */
    public function usedDaysInMonth(User $user, int $year, int $month): int
    {
        return (int) $this->leaveRequests()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->sum('days');
    }

    /**
     * Days rolled over from the prior year — a single hop only (the prior
     * year's own carry-in is never re-carried, so this never compounds),
     * capped at max_carry_forward_days if one is set.
     *
     * max_accumulation_days caps the TOTAL banked balance (base allowance +
     * carry-in), but only ever trims the carry-in portion — it never eats
     * into the current year's base allowance itself. Without this, setting
     * it to a value at or below days_per_year (including 0, e.g. left blank
     * by mistake) would zero out the entire leave type, not just carry-over.
     */
    public function carriedInDays(User $user, int $year): int
    {
        if (! $this->carry_forward) {
            return 0;
        }

        $unused = max(0, $this->days_per_year - $this->usedDays($user, $year - 1));
        $carriedIn = $this->max_carry_forward_days !== null ? min($unused, $this->max_carry_forward_days) : $unused;

        if ($this->max_accumulation_days !== null) {
            $carriedIn = min($carriedIn, max(0, $this->max_accumulation_days - $this->days_per_year));
        }

        return $carriedIn;
    }

    public function balanceFor(User $user, int $year): array
    {
        $carriedIn = $this->carriedInDays($user, $year);
        $allowance = $this->days_per_year + $carriedIn;
        $used = $this->usedDays($user, $year);

        return [
            'allowance' => $allowance,
            'carried_in' => $carriedIn,
            'used' => $used,
            'remaining' => max(0, $allowance - $used),
        ];
    }
}
