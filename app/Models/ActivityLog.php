<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'properties' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $action, string $description, ?Model $subject = null, array $properties = []): self
    {
        $tenantId = app()->bound('currentTenant') ? app('currentTenant')?->id : null;
        $tenantId ??= auth()->user()?->tenant_id;

        $routeName = optional(request()->route())->getName();
        $portal = $routeName ? explode('.', $routeName)[0] : null;

        return static::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'portal' => $portal,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
