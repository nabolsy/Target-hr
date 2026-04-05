<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (! $model->company_id && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where($builder->getModel()->getTable() . '.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
