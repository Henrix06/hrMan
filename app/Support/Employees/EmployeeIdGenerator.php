<?php

namespace App\Support\Employees;

use App\Models\User;

class EmployeeIdGenerator
{
    public function next(): string
    {
        $lastEmployee = User::query()
            ->whereNotNull('employee_id')
            ->orderByDesc('employee_id')
            ->first();

        if ($lastEmployee && preg_match('/EMP(\d+)/', $lastEmployee->employee_id, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return 'EMP'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
