<?php

namespace App\Actions\Employees;

use App\Models\User;
use App\Support\Employees\HrProfileFields;
use Illuminate\Support\Facades\Hash;

class UpdateEmployeeRecord
{
    public function execute(User $employee, array $data): User
    {
        $attributes = array_merge([
            'name' => $data['name'],
            'email' => $data['email'],
            'employee_id' => $data['employee_id'],
            'manager_id' => $data['manager_id'] ?: null,
            'hire_date' => $data['hire_date'] ?: null,
            'status' => $data['status'],
            'base_salary' => $data['base_salary'] ?: null,
            'department' => $data['department'],
            'position' => $data['position'],
            'phone' => $data['phone'],
        ], HrProfileFields::extract($data));

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $employee->update($attributes);
        $employee->syncRoles([$data['role']]);

        return $employee->refresh();
    }
}
