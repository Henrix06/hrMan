<?php

namespace App\Actions\Employees;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

class InitializeLeaveBalances
{
    /**
     * Give a newly created user a balance row for every active leave type.
     */
    public function execute(User $user): void
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
