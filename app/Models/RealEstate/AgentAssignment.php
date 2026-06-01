<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAssignment extends Model
{
    use HasRealEstateReference;

    protected $table = 're_agent_assignments';

    protected $fillable = [
        'reference', 'company_tenant_id', 'unit_id', 'agent_id', 'assigned_by',
        'agent_role', 'start_date', 'end_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('company_tenant_id', auth()->id());
            }
        });

        static::creating(function ($assignment) {
            if (empty($assignment->reference)) {
                $assignment->reference = self::generateReference('ASSIGN');
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function companyTenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_tenant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAgent(Builder $query): Builder
    {
        return $query->where('agent_role', 'agent');
    }

    public function scopeManager(Builder $query): Builder
    {
        return $query->where('agent_role', 'manager');
    }
}
