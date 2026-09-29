<?php

namespace App\Actions\Employees;

use App\Models\User;
use App\Support\Employees\EmployeeIdGenerator;
use App\Support\Employees\HrProfileFields;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeRecord
{
    public function __construct(
        private readonly EmployeeIdGenerator $employeeIdGenerator,
        private readonly InitializeLeaveBalances $leaveBalances,
    ) {}

    public function execute(array $data): User
    {
        $user = User::create(array_merge([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'employee_id' => $data['employee_id'] ?: $this->employeeIdGenerator->next(),
            'manager_id' => $data['manager_id'] ?: null,
            'hire_date' => $data['hire_date'] ?: null,
            'status' => $data['status'],
            'base_salary' => $data['base_salary'] ?: null,
            'department' => $data['department'],
            'position' => $data['position'],
            'phone' => $data['phone'],
            'tenant_id' => auth()->user()->tenant_id,
        ], HrProfileFields::extract($data)));

        $user->assignRole($data['role']);

        $this->leaveBalances->execute($user);

        return $user;
    }
}
