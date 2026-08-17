<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'start_time',
        'end_time',
        'grace_minutes',
        'break_minutes',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * This shift's start, anchored to a specific work date. Accepts an
     * optional per-employee override (e.g. User::custom_start_time or the
     * same snapshotted onto an Attendance row) in place of this shift's own
     * start_time — the shift itself stays the shared/canonical definition.
     */
    public function startDateTimeFor(Carbon $workDate, ?string $overrideStart = null): Carbon
    {
        return Carbon::parse($workDate->format('Y-m-d').' '.Carbon::parse($overrideStart ?: $this->start_time)->format('H:i:s'));
    }

    /**
     * This shift's end, anchored to a specific work date — pushed a day
     * forward when the (possibly overridden) shift crosses midnight. Pass
     * both overrides together — an employee's custom hours are always
     * start+end as a pair, never just one side.
     */
    public function endDateTimeFor(Carbon $workDate, ?string $overrideStart = null, ?string $overrideEnd = null): Carbon
    {
        $end = Carbon::parse($workDate->format('Y-m-d').' '.Carbon::parse($overrideEnd ?: $this->end_time)->format('H:i:s'));

        if ($end->lessThanOrEqualTo($this->startDateTimeFor($workDate, $overrideStart))) {
            $end->addDay();
        }

        return $end;
    }

    public function formattedRange(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A').' – '.Carbon::parse($this->end_time)->format('g:i A');
    }
}
