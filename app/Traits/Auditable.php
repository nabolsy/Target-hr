<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            self::logAuditToDatabase('created', $model);
        });

        static::updated(function ($model) {
            self::logAuditToDatabase('updated', $model);
        });

        static::deleted(function ($model) {
            self::logAuditToDatabase('deleted', $model);
        });
    }

    protected static function logAuditToDatabase(string $action, $model): void
    {
        $oldValues = null;
        $newValues = null;

        $hidden = $model->getHidden();

        switch ($action) {
            case 'created':
                $newValues = collect($model->getAttributes())
                    ->except($hidden)
                    ->toArray();
                break;

            case 'updated':
                $dirty = $model->getDirty();
                $original = collect($model->getOriginal())
                    ->only(array_keys($dirty))
                    ->except($hidden)
                    ->toArray();
                $newValues = collect($dirty)
                    ->except($hidden)
                    ->toArray();
                $oldValues = $original;

                // Skip if no visible changes
                if (empty($newValues)) {
                    return;
                }
                break;

            case 'deleted':
                $oldValues = collect($model->getAttributes())
                    ->except($hidden)
                    ->toArray();
                break;
        }

        AuditLog::create([
            'company_id' => $model->company_id ?? null,
            'user_id' => auth()->check() ? auth()->id() : null,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
