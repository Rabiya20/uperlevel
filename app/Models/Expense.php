<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    /**
     * Matches the 5-state workflow from spec exactly — a rejected expense
     * goes back to "draft" with rejection_reason set, rather than a 6th
     * status the spec doesn't list.
     */
    public const STATUSES = ['draft', 'pending_approval', 'approved', 'paid', 'cancelled'];

    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'cheque', 'card', 'online'];

    public const PAYMENT_STATUSES = ['paid', 'unpaid', 'partial'];

    public const RECURRENCE_INTERVALS = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

    protected $fillable = [
        'tenant_id',
        'expense_number',
        'vendor_id',
        'reference_number',
        'description',
        'expense_date',
        'project_id',
        'payment_account_id',
        'payment_method',
        'payment_status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'is_recurring',
        'recurrence_interval',
        'next_occurrence_date',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'edit_reason',
        'deleted_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_recurring' => 'boolean',
        'next_occurrence_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseLine::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function auditLabel(): string
    {
        return "Expense {$this->expense_number}";
    }

    /** Locked once it's left draft/pending_approval — same "don't rewrite settled history" rule used for Invoices. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval'], true);
    }

    public function approve(User $approver): void
    {
        $this->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    /** Sends it back to draft rather than a separate "rejected" status — the workflow only has 5 states. */
    public function reject(User $approver, ?string $reason = null): void
    {
        $this->update(['status' => 'draft', 'approved_by' => $approver->id, 'approved_at' => now(), 'rejection_reason' => $reason]);
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'paid', 'payment_status' => 'paid']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /** Recomputes subtotal/total from the current lines — call after saving lines, before saving the header's totals. */
    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('amount');
        $total = round($subtotal + $this->tax_amount - $this->discount_amount, 2);

        $this->update(['subtotal' => $subtotal, 'total_amount' => $total]);
    }

    /** Clones the header (as a fresh draft, no approval/attachment history) and its lines. */
    public function duplicate(User $actor, string $newExpenseNumber): self
    {
        $copy = static::create([
            'tenant_id' => $this->tenant_id,
            'expense_number' => $newExpenseNumber,
            'vendor_id' => $this->vendor_id,
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'expense_date' => now()->toDateString(),
            'project_id' => $this->project_id,
            'payment_account_id' => $this->payment_account_id,
            'payment_method' => $this->payment_method,
            'payment_status' => 'unpaid',
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'status' => 'draft',
            'created_by' => $actor->id,
        ]);

        foreach ($this->lines as $line) {
            $copy->lines()->create([
                'expense_category_id' => $line->expense_category_id,
                'client_id' => $line->client_id,
                'project_id' => $line->project_id,
                'department_id' => $line->department_id,
                'description' => $line->description,
                'class_tag' => $line->class_tag,
                'amount' => $line->amount,
            ]);
        }

        $copy->recalculateTotals();

        return $copy;
    }

    /** Creates the concrete occurrence for this recurring series' due date, then rolls the pointer forward. */
    public function generateNextOccurrence(): self
    {
        $occurrence = $this->duplicate($this->creator ?? auth()->user(), $this->next_occurrence_date->format('Ymd').'-REC-'.$this->id);
        $occurrence->update(['expense_date' => $this->next_occurrence_date, 'is_recurring' => false]);

        $this->update(['next_occurrence_date' => $this->nextIntervalDate($this->next_occurrence_date)]);

        return $occurrence;
    }

    private function nextIntervalDate(\Illuminate\Support\Carbon $from): \Illuminate\Support\Carbon
    {
        return match ($this->recurrence_interval) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'quarterly' => $from->copy()->addMonths(3),
            'yearly' => $from->copy()->addYear(),
            default => $from->copy()->addMonth(),
        };
    }
}
