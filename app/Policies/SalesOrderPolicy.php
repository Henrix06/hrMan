<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    /**
     * Anyone who can work in the module at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view sales');
    }

    /**
     * The person who logged the order, or someone who can manage sales.
     */
    public function view(User $user, SalesOrder $order): bool
    {
        return $order->sold_by === $user->id || $user->can('manage sales');
    }

    public function create(User $user): bool
    {
        return $user->can('create sales order');
    }

    public function update(User $user, SalesOrder $order): bool
    {
        if (! $order->isEditable()) {
            return false;
        }

        return $order->sold_by === $user->id || $user->can('manage sales');
    }

    public function delete(User $user, SalesOrder $order): bool
    {
        return $user->isHRAdmin();
    }
}
