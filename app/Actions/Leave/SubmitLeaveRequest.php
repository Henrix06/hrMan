<?php

namespace App\Actions\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\Leave\BusinessDayCalculator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitLeaveRequest
{
    public function __construct(
        private readonly BusinessDayCalculator $businessDayCalculator,
    ) {
    }

    public function execute(User $user, array $data): LeaveRequest
    {
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $totalDays = $this->businessDayCalculator->count($data['start_date'], $data['end_date']);

        if ($totalDays <= 0) {
            throw new RuntimeException('Total days must be greater than 0 (weekends excluded).');
        }

        return DB::transaction(function () use ($data, $leaveType, $totalDays, $user) {
            $leaveBalance = LeaveBalance::query()
                ->where('user_id', $user->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', now()->year)
                ->lockForUpdate()
                ->first();

            if (!$leaveBalance || $leaveBalance->remaining_days < $totalDays) {
                $availableDays = $leaveBalance?->remaining_days ?? 0;

                throw new RuntimeException("Insufficient leave balance. Available: {$availableDays}");
            }

            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);

            $leaveBalance->increment('days_pending', $totalDays);

            return $leaveRequest;
        });
    }
}
