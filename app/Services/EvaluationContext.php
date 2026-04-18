<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EvaluationContext
{
    public string $plan;
    public int $employee_count;
    public ?string $user_role;
    public ?string $module_key = null;
    public string $date;
    public int $day_of_week;

    public function __construct(?int $tenantId = null)
    {
        $tenantId ??= 1;

        // Resolve plan — default to 'free' (single-tenant: no tenant record yet)
        $this->plan = config('pricing.plans.' . $this->resolvePlan($tenantId) . '.name')
            ? $this->resolvePlan($tenantId)
            : 'free';

        // Count active employees
        $this->employee_count = User::where('status', 'active')->count();

        // Authenticated user's primary role
        $user = Auth::user();
        $this->user_role = $user?->getRoleNames()->first();

        // Temporal fields
        $this->date        = now()->toDateString();
        $this->day_of_week = (int) now()->dayOfWeek;
    }

    public function toArray(): array
    {
        return [
            'plan'           => $this->plan,
            'employee_count' => $this->employee_count,
            'user_role'      => $this->user_role,
            'module_key'     => $this->module_key,
            'date'           => $this->date,
            'day_of_week'    => $this->day_of_week,
        ];
    }

    private function resolvePlan(int $tenantId): string
    {
        // Single-tenant: read from config or env; defaults to 'free'
        return config('app.plan', 'free');
    }
}
