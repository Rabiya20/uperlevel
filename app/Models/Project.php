<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'manager_id',
        'created_by',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function auditLabel(): string
    {
        return "Project \"{$this->name}\"";
    }

    /** No invoice tied to this project is still in draft/sent — everything billed against it has been settled. */
    public function allPaymentsDone(): bool
    {
        return ! $this->invoices()->whereNotIn('status', ['paid', 'cancelled'])->exists();
    }

    /** Archiving requires the work to be marked done and every bill for it settled — never archives an unfinished or unpaid project. */
    public function isArchivable(): bool
    {
        return $this->status === 'completed' && ! $this->archived_at && $this->allPaymentsDone();
    }
}
