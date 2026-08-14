<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserModulePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserModuleService
{
    /**
     * Check if a user can access a module.
     * Super Admin and HR Admin always have full access.
     */
    public function canAccess(User $user, string $moduleKey): bool
    {
        // Admins bypass per-user module restrictions
        if ($user->hasRole(['Super Admin', 'HR Admin'])) {
            return true;
        }

        return Cache::remember(
            "user_module:{$user->id}:{$moduleKey}",
            300,
            fn () => UserModulePermission::where('user_id', $user->id)
                ->where('module_key', $moduleKey)
                ->where('is_enabled', true)
                ->exists()
        );
    }

    /**
     * Get all module keys enabled for a user.
     */
    public function enabledModules(User $user): Collection
    {
        if ($user->hasRole(['Super Admin', 'HR Admin'])) {
            return collect(array_keys(config('pricing.modules', [])));
        }

        return UserModulePermission::where('user_id', $user->id)
            ->where('is_enabled', true)
            ->pluck('module_key');
    }

    /**
     * Sync a user's module permissions (replaces all existing).
     */
    public function syncModules(User $user, array $moduleKeys): void
    {
        $allModules = array_keys(config('pricing.modules', []));

        $rows = collect($allModules)->mapWithKeys(fn ($key) => [
            $key => [
                'user_id'    => $user->id,
                'module_key' => $key,
                'is_enabled' => in_array($key, $moduleKeys, true),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        ])->values()->all();

        foreach ($rows as $row) {
            UserModulePermission::updateOrCreate(
                ['user_id' => $row['user_id'], 'module_key' => $row['module_key']],
                ['is_enabled' => $row['is_enabled']]
            );
        }

        $this->clearCache($user);
    }

    public function clearCache(User $user): void
    {
        foreach (array_keys(config('pricing.modules', [])) as $key) {
            Cache::forget("user_module:{$user->id}:{$key}");
        }
    }
}
