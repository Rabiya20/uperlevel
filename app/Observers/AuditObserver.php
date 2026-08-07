<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic created/updated/deleted logger, registered per-model in
 * AppServiceProvider for the app's admin-configurable CRUD/settings
 * models. Not registered for models with their own richer logging
 * (Lead) or high-frequency routine writes (Attendance, User).
 */
class AuditObserver
{
    private const HIDDEN = [
        'updated_at', 'created_at', 'tenant_id', 'password', 'remember_token',
        'payment_gateway_secret_key', 'payment_gateway_webhook_secret',
    ];

    public function created(Model $model): void
    {
        ActivityLog::record('created', 'Created '.$this->label($model).'.', $model);
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())->except(self::HIDDEN);

        if ($changes->isEmpty()) {
            return;
        }

        $summary = $changes->map(fn ($value, $key) => "{$key}: ".$this->fmt($model->getOriginal($key)).' → '.$this->fmt($value))->implode(', ');

        ActivityLog::record('updated', 'Updated '.$this->label($model).': '.$summary, $model, $changes->toArray());
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record('deleted', 'Deleted '.$this->label($model).'.', $model);
    }

    private function label(Model $model): string
    {
        if (method_exists($model, 'auditLabel')) {
            return $model->auditLabel();
        }

        $name = $model->name ?? $model->title ?? null;

        return class_basename($model).($name ? " \"{$name}\"" : ' #'.$model->getKey());
    }

    private function fmt(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value === null || $value === '' ? '—' : (string) $value;
    }
}
