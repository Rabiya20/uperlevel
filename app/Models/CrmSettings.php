<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmSettings extends Model
{
    public const DEFAULT_STAGES = [
        ['key' => 'new', 'label' => 'New', 'is_won' => false, 'is_lost' => false],
        ['key' => 'contacted', 'label' => 'Contacted', 'is_won' => false, 'is_lost' => false],
        ['key' => 'discussion', 'label' => 'Discussion', 'is_won' => false, 'is_lost' => false],
        ['key' => 'proposal-sent', 'label' => 'Proposal Sent', 'is_won' => false, 'is_lost' => false],
        ['key' => 'negotiation', 'label' => 'Negotiation', 'is_won' => false, 'is_lost' => false],
        ['key' => 'won', 'label' => 'Won', 'is_won' => true, 'is_lost' => false],
        ['key' => 'lost', 'label' => 'Lost', 'is_won' => false, 'is_lost' => true],
    ];

    public const DEFAULT_SOURCES = ['Website', 'Fiverr', 'Upwork', 'Referral', 'Other'];

    protected $fillable = [
        'tenant_id',
        'pipeline_stages',
        'lead_sources',
        'assignment_mode',
        'rule_based_field',
        'approval_required',
        'duplicate_handling',
    ];

    protected $casts = [
        'pipeline_stages' => 'array',
        'lead_sources' => 'array',
        'approval_required' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function auditLabel(): string
    {
        return 'CRM Settings';
    }

    public static function forTenant(Tenant $tenant): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['pipeline_stages' => self::DEFAULT_STAGES, 'lead_sources' => self::DEFAULT_SOURCES]
        );
    }

    public function stageLabel(string $key): string
    {
        return collect($this->pipeline_stages)->firstWhere('key', $key)['label'] ?? $key;
    }

    public function stage(string $key): ?array
    {
        return collect($this->pipeline_stages)->firstWhere('key', $key);
    }

    public function wonStageKey(): ?string
    {
        return collect($this->pipeline_stages)->firstWhere('is_won', true)['key'] ?? null;
    }

    public function lostStageKey(): ?string
    {
        return collect($this->pipeline_stages)->firstWhere('is_lost', true)['key'] ?? null;
    }

    public function firstStageKey(): string
    {
        return $this->pipeline_stages[0]['key'] ?? 'new';
    }
}
