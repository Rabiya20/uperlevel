<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'card', 'online'];

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'client_id',
        'amount',
        'payment_date',
        'payment_method',
        'deposit_account_id',
        'reference_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'deposit_account_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function auditLabel(): string
    {
        return 'Payment of '.$this->amount.' for invoice '.($this->invoice->invoice_number ?? '');
    }
}
