<?php

namespace App\Policies\RealEstate;

use App\Models\User;

class RentInvoicePolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.view');
    }

    public function markPaid(User $user): bool
    {
        return $user->hasPermissionTo('real_estate.manage_invoices');
    }
}
