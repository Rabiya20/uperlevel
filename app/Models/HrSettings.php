<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrSettings extends Model
{
    public const DEFAULT_WORKING_DAYS = [1, 2, 3, 4, 5]; // Mon-Fri (Carbon dayOfWeek: 0=Sun..6=Sat)

    protected $fillable = [
        'tenant_id',
        'overtime_enabled',
        'overtime_multiplier',
        'overtime_threshold_minutes',
        'working_days',
        'payroll_cycle',
        'payroll_pay_day',
        'payroll_locked_through',
        'employee_code_prefix',
    ];

    protected $casts = [
        'overtime_enabled' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'working_days' => 'array',
        'payroll_locked_through' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function auditLabel(): string
    {
        return 'HR Settings';
    }

    public static function forTenant(Tenant $tenant): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['working_days' => self::DEFAULT_WORKING_DAYS]
        );
    }

    public function isWorkingDay(\Carbon\Carbon $date): bool
    {
        return in_array($date->dayOfWeek, $this->working_days ?? self::DEFAULT_WORKING_DAYS, true);
    }

    public function isPayrollLocked(\Carbon\Carbon $date): bool
    {
        return $this->payroll_locked_through !== null
            && $date->startOfDay()->lessThanOrEqualTo($this->payroll_locked_through);
    }
}
