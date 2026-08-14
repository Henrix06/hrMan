<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const TTL = 3600; // 1 hour

    public function departments(): Collection
    {
        return Cache::remember('cache_svc:departments:all', self::TTL, fn() =>
            Department::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
        );
    }

    public function employees(): Collection
    {
        return Cache::remember('cache_svc:employees:active', self::TTL, fn() =>
            User::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'department_id', 'position'])
        );
    }

    public function roles(): Collection
    {
        return Cache::remember('cache_svc:roles:all', self::TTL, fn() =>
            \Spatie\Permission\Models\Role::orderBy('name')->get(['id', 'name'])
        );
    }

    public function clearAll(): void
    {
        Cache::forget('cache_svc:departments:all');
        Cache::forget('cache_svc:employees:active');
        Cache::forget('cache_svc:roles:all');
    }
}
