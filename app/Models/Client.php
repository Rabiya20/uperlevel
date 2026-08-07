<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_id',
        'converted_by',
        'name',
        'company_name',
        'email',
        'phone',
        'country',
        'source',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** The lead that originally created this client record. */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** Every lead that has ever matched this client, including later re-conversions reused via findDuplicate(). */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    /** Duplicate check for lead-to-client conversion — email, phone or company name match within the tenant. */
    public static function findDuplicate(int $tenantId, ?string $email, ?string $phone, ?string $companyName): ?self
    {
        if (blank($email) && blank($phone) && blank($companyName)) {
            return null;
        }

        return static::where('tenant_id', $tenantId)
            ->where(function ($q) use ($email, $phone, $companyName) {
                $q->when(filled($email), fn ($q2) => $q2->orWhere('email', $email))
                    ->when(filled($phone), fn ($q2) => $q2->orWhere('phone', $phone))
                    ->when(filled($companyName), fn ($q2) => $q2->orWhere('company_name', $companyName));
            })
            ->first();
    }
}
