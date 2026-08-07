<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenCapture extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'file_path',
        'original_filename',
        'captured_at',
        'device_name',
        'client_ip',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
