<?php

namespace App\Actions\Employees;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\Employees\EmployeeIdGenerator;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeRecord
{
    public function __construct(
        private readonly EmployeeIdGenerator $employeeIdGenerator,
    ) {
    }

    public function execute(array $data): User
    {
        $user = User::create([
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
        ]);

        $user->assignRole($data['role']);

        $this->initializeLeaveBalances($user);

        return $user;
    }

    private function initializeLeaveBalances(User $user): void
    {
        $currentYear = now()->year;

        LeaveType::where('active', true)->each(function (LeaveType $leaveType) use ($user, $currentYear) {
            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $currentYear,
                ],
                [
                    'days_available' => $leaveType->default_days,
                    'days_taken' => 0,
                    'days_pending' => 0,
                ]
            );
        });
    }
}
