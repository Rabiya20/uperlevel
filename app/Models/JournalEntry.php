<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Posts a balanced double-entry journal entry — $lines is an array of
     * ['chart_of_account_id' => int, 'debit' => float, 'credit' => float]
     * rows (at least 2, one of each side at minimum). Refuses to persist
     * anything where total debits don't exactly equal total credits.
     *
     * $referenceNumber/$payee/$transactionType are denormalized onto the
     * entry (rather than looked up via $source at read time) so the
     * per-account ledger can search/filter without a polymorphic join.
     */
    public static function post(
        int $tenantId,
        Carbon $date,
        string $memo,
        array $lines,
        ?Model $source = null,
        ?int $createdBy = null,
        string $referenceNumber = '',
        string $payee = '',
        string $transactionType = 'manual',
    ): self {
        if (count($lines) < 2) {
            throw new \RuntimeException('A journal entry needs at least two lines.');
        }

        foreach ($lines as $line) {
            if (round((float) ($line['debit'] ?? 0), 2) > 0 && round((float) ($line['credit'] ?? 0), 2) > 0) {
                throw new \RuntimeException('A single line can\'t be both a debit and a credit.');
            }
        }

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if ($totalDebit !== $totalCredit) {
            throw new \RuntimeException("Journal entry is not balanced — debits ({$totalDebit}) must equal credits ({$totalCredit}).");
        }

        return DB::transaction(function () use ($tenantId, $date, $memo, $lines, $source, $createdBy, $referenceNumber, $payee, $transactionType) {
            $entry = static::create([
                'tenant_id' => $tenantId,
                'created_by' => $createdBy,
                'entry_date' => $date->toDateString(),
                'memo' => $memo,
                'reference_number' => $referenceNumber ?: null,
                'payee' => $payee ?: null,
                'transaction_type' => $transactionType,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);

            foreach ($lines as $line) {
                if (round((float) ($line['debit'] ?? 0), 2) === 0.0 && round((float) ($line['credit'] ?? 0), 2) === 0.0) {
                    continue; // skip zero-value lines (e.g. no tax/discount on this transaction)
                }

                $entry->lines()->create([
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $entry;
        });
    }
}
