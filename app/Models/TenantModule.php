<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModule extends Model
{
    protected $fillable = ['tenant_id', 'module_key', 'is_enabled', 'metadata'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata'   => 'array',
    ];
}
