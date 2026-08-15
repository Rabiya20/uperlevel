<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'company_name',
        'email',
        'phone',
        'source',
        'country',
        'budget',
        'currency',
        'description',
        'status',
        'previous_status',
        'assigned_employee_id',
        'created_by',
        'is_locked',
        'conversion_status',
        'client_id',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'is_locked' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivityLog::class)->latest('created_at');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class)->orderBy('follow_up_at');
    }

    /** Owner/admin see every tenant lead; employees only their own. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwnerOrAdmin()) {
            return $query;
        }

        return $query->where('assigned_employee_id', $user->id);
    }

    public function authorizeAccess(User $user): void
    {
        abort_unless($this->tenant_id === $user->tenant_id, 404);

        if (! $user->isOwnerOrAdmin() && $this->assigned_employee_id !== $user->id) {
            abort(404);
        }
    }

    /** Locked leads (pending/approved conversion) are frozen for employees. */
    public function canEdit(User $user): bool
    {
        return $user->isOwnerOrAdmin() || ! $this->is_locked;
    }

    public function logActivity(string $type, ?User $actor, ?string $description = null, ?array $meta = null): LeadActivityLog
    {
        $entry = $this->activities()->create([
            'tenant_id' => $this->tenant_id,
            'user_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
        ]);

        ActivityLog::record($type, ($description ?: ucfirst($type)).' — Lead "'.$this->name.'".', $this, $meta ?? []);

        return $entry;
    }

    /**
     * Moves the lead to a new pipeline stage. Reaching the "Won" stage
     * either converts immediately (owner/admin — they'd just be approving
     * their own request) or locks the lead and queues it for approval
     * (employee, when crm_settings.approval_required is on).
     */
    public function changeStatus(string $newKey, User $actor): ?Client
    {
        if ($this->is_locked && ! $actor->isOwnerOrAdmin()) {
            abort(403, 'This lead is locked pending approval.');
        }

        $settings = CrmSettings::forTenant($this->tenant);
        $stage = $settings->stage($newKey);
        abort_unless($stage, 422, 'Unknown pipeline stage.');

        $oldKey = $this->status;
        $this->status = $newKey;
        $this->save();
        $this->logActivity('status_change', $actor, "Status changed to \"{$stage['label']}\".", ['from' => $oldKey, 'to' => $newKey]);

        if (! $stage['is_won']) {
            return null;
        }

        if ($actor->isOwnerOrAdmin()) {
            return $this->convertToClient($actor);
        }

        if ($settings->approval_required) {
            $this->previous_status = $oldKey;
            $this->is_locked = true;
            $this->conversion_status = 'pending_approval';
            $this->save();
            $this->logActivity('conversion_request', $actor, 'Requested conversion approval.');

            return null;
        }

        return $this->convertToClient($actor);
    }

    /**
     * Reuses an existing client (matched by email/phone/company name within
     * the tenant) instead of creating a duplicate when the same company
     * converts via more than one lead — the existing client's own fields are
     * never overwritten by a later, possibly lower-quality, lead match.
     * Callers can tell new-vs-reused apart via Client::$wasRecentlyCreated.
     */
    public function convertToClient(User $approver): Client
    {
        $client = Client::findDuplicate($this->tenant_id, $this->email, $this->phone, $this->company_name)
            ?? Client::create([
                'tenant_id' => $this->tenant_id,
                'lead_id' => $this->id,
                'converted_by' => $approver->id,
                'name' => $this->name,
                'company_name' => $this->company_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'country' => $this->country,
                'source' => $this->source,
            ]);

        $this->client_id = $client->id;
        $this->conversion_status = 'approved';
        $this->is_locked = true;
        $this->save();

        $this->logActivity('converted', $approver, $client->wasRecentlyCreated
            ? 'Lead converted — added as a new client.'
            : "Lead converted — matched to the existing client \"{$client->name}\".");

        return $client;
    }

    public function rejectConversion(User $approver, ?string $reason = null): void
    {
        $this->status = $this->previous_status ?? $this->status;
        $this->previous_status = null;
        $this->is_locked = false;
        $this->conversion_status = 'rejected';
        $this->save();

        $this->logActivity('conversion_rejected', $approver, $reason ?: 'Conversion rejected — sent back to employee.');
    }

    /** Duplicate check for manual create — email, phone or company name match within the tenant. */
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
