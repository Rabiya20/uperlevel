<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    public const TYPES = ['asset', 'liability', 'equity', 'income', 'expense'];

    /** Accounts where a healthy balance grows with credits, not debits — the opposite of asset/expense. */
    public const CREDIT_NORMAL_TYPES = ['liability', 'equity', 'income'];

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Opening balance plus every journal movement since — expressed in
     * "normal balance" terms for this account's type, so a healthy
     * liability/equity/income account shows as a positive number even
     * though it's carried as credit-heavy in the raw debit/credit lines.
     */
    public function currentBalance(): float
    {
        $debits = (float) $this->lines()->sum('debit');
        $credits = (float) $this->lines()->sum('credit');

        $movement = $this->isCreditNormal() ? $credits - $debits : $debits - $credits;

        return round((float) $this->opening_balance + $movement, 2);
    }

    public function isCreditNormal(): bool
    {
        return in_array($this->type, self::CREDIT_NORMAL_TYPES, true);
    }

    /** Signed movement of a single journal line, in this account's own normal-balance terms. */
    public function lineMovement(JournalEntryLine $line): float
    {
        return $this->isCreditNormal()
            ? (float) $line->credit - (float) $line->debit
            : (float) $line->debit - (float) $line->credit;
    }
}
