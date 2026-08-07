<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenCaptureSettings extends Model
{
    public const INTERVAL_MODES = ['fixed', 'random'];

    protected $fillable = [
        'tenant_id',
        'enabled',
        'interval_mode',
        'interval_minutes',
        'random_min_minutes',
        'random_max_minutes',
        'retention_days',
        'notify_employees',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'notify_employees' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function auditLabel(): string
    {
        return 'Screen Capture Settings';
    }

    public static function forTenant(Tenant $tenant): self
    {
        // firstOrCreate()'s in-memory object only reflects attributes we
        // pass here — a bare INSERT would leave these null in PHP even
        // though the DB column defaults would apply, until the row is
        // reloaded. Passing them explicitly keeps first-use and
        // every-later-use identical.
        return static::firstOrCreate(['tenant_id' => $tenant->id], [
            'enabled' => false,
            'interval_mode' => 'fixed',
            'interval_minutes' => 60,
            'retention_days' => 30,
            'notify_employees' => true,
        ]);
    }
}
