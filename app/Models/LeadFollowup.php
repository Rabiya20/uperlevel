<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_id',
        'employee_id',
        'follow_up_at',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function isMissed(): bool
    {
        return ! $this->completed_at && $this->follow_up_at->isPast();
    }
}
