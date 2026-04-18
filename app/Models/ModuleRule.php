<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleRule extends Model
{
    protected $fillable = ['tenant_id', 'subject', 'rule_key', 'definition', 'is_active', 'priority'];

    protected $casts = [
        'is_active'  => 'boolean',
        'definition' => 'array',
        'priority'   => 'integer',
    ];
}
