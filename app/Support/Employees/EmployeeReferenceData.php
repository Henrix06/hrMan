<?php

namespace App\Support\Employees;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class EmployeeReferenceData
{
    public function managers(?int $exceptUserId = null): Collection
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereHas('roles', function (Builder $query) {
                $query->whereIn('name', ['Manager', 'HR Admin', 'Super Admin']);
            })
            ->orderBy('name');

        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->get();
    }

    public function assignableRoles(): Collection
    {
        return Role::query()
            ->whereNotIn('name', ['Super Admin'])
            ->orderBy('name')
            ->get();
    }
}
