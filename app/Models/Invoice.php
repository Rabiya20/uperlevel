<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'project_id',
        'created_by',
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_percentage',
        'tax_amount',
        'total',
        'notes',
        'overdue_notified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'overdue_notified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function auditLabel(): string
    {
        return "Invoice {$this->invoice_number}";
    }

    /** Computed, not stored — a "sent" invoice past its due date (+ grace period), with no scheduled job needed to flip a status. */
    public function isOverdue(int $graceDays = 0): bool
    {
        return $this->status === 'sent' && $this->due_date->copy()->addDays($graceDays)->isPast();
    }

    /** Derived from actual payment records, never stored — a single source of truth for how much has come in. */
    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total - $this->amountPaid(), 2));
    }

    public function paymentStatus(): string
    {
        if ($this->balanceDue() <= 0 && $this->amountPaid() > 0) {
            return 'paid';
        }

        return $this->amountPaid() > 0 ? 'partial' : 'unpaid';
    }
}
