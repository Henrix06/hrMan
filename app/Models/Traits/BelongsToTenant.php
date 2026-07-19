<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (is_null($model->getAttribute('tenant_id')) && auth()->check()) {
                $model->setAttribute('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
