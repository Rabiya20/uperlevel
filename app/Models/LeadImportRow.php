<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'name',
        'company_name',
        'email',
        'phone',
        'source',
        'country',
        'budget',
        'description',
        'status',
        'error_message',
        'duplicate_lead_id',
        'imported_lead_id',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LeadImportBatch::class, 'batch_id');
    }

    public function duplicateLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'duplicate_lead_id');
    }

    public function importedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'imported_lead_id');
    }
}
