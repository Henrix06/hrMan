<?php

namespace App\Support\Employees;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeFilters
{
    public function apply(Builder $query, array $filters): Builder
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $department = $filters['department'] ?? '';
        $role = $filters['role'] ?? '';

        return $query
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($department, fn (Builder $query, string $department) => $query->where('department', $department))
            ->when($role, fn (Builder $query, string $role) => $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role)));
    }

    public function departments(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\Cache::remember('employee_departments', 300, fn() =>
            User::query()
                ->whereNotNull('department')
                ->orderBy('department')
                ->distinct()
                ->pluck('department')
        );
    }
}
