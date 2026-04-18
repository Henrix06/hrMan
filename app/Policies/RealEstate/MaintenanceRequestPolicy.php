<?php

namespace App\Policies\RealEstate;

use App\Models\User;

class MaintenanceRequestPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.view');
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_maintenance');
    }

    public function resolve(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_maintenance');
    }

    public function close(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_maintenance');
    }

    public function cancel(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_maintenance');
    }
}
