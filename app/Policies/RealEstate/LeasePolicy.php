<?php

namespace App\Policies\RealEstate;

use App\Models\User;

class LeasePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_leases');
    }

    public function activate(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_leases');
    }

    public function renew(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_leases');
    }

    public function terminate(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_leases');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.view');
    }
}
