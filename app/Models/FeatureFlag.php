<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['tenant_id', 'flag_key', 'is_enabled', 'description'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
