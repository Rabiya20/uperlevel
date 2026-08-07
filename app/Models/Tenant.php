<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_initials', 'plan', 'status', 'timezone'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_module')
            ->withPivot('enabled')
            ->withTimestamps();
    }

    public function financeSettings(): HasOne
    {
        return $this->hasOne(FinanceSettings::class);
    }

    /**
     * Modules actually switched on for this tenant (used to filter nav).
     */
    public function enabledModuleIds(): array
    {
        return $this->modules()->wherePivot('enabled', true)->pluck('modules.id')->all();
    }

    public function owner(): ?User
    {
        return $this->users()->where('role', 'owner')->first();
    }

    /**
     * Every timestamp is stored in UTC — this converts one to the
     * tenant's chosen display timezone (set in Company > Settings)
     * without touching the stored value.
     */
    public function localizeTime(?\Illuminate\Support\Carbon $dt): ?\Illuminate\Support\Carbon
    {
        return $dt?->copy()->setTimezone($this->timezone ?: 'UTC');
    }

    public function localNow(): \Illuminate\Support\Carbon
    {
        return now()->setTimezone($this->timezone ?: 'UTC');
    }
}