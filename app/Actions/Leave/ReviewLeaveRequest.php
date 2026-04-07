<?php

namespace App\Actions\Leave;

use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReviewLeaveRequest
{
    public function approve(LeaveRequest $leaveRequest, User $reviewer): void
    {
        $this->ensurePending($leaveRequest);

        DB::transaction(function () use ($leaveRequest, $reviewer) {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'approval_remarks' => null,
            ]);

            $leaveBalance = $this->leaveBalanceFor($leaveRequest);

            if ($leaveBalance) {
                $pendingDaysToRelease = min($leaveRequest->total_days, $leaveBalance->days_pending);
                if ($pendingDaysToRelease > 0) {
                    $leaveBalance->decrement('days_pending', $pendingDaysToRelease);
                }
                $leaveBalance->increment('days_taken', $pendingDaysToRelease);
            }
        });

        AuditLog::record('update', "Approved leave request #{$leaveRequest->id} for user #{$leaveRequest->user_id}", $leaveRequest);
        HrNotification::notify($leaveRequest->user_id, 'leave_approved', 'Leave Request Approved',
            "Your leave request has been approved by {$reviewer->name}.", route('leave.apply'));
    }

    public function reject(LeaveRequest $leaveRequest, User $reviewer): void
    {
        $this->ensurePending($leaveRequest);

        DB::transaction(function () use ($leaveRequest, $reviewer) {
            $leaveRequest->update([
                'status' => 'rejected',
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'approval_remarks' => 'Rejected by '.$reviewer->name,
            ]);

            $leaveBalance = $this->leaveBalanceFor($leaveRequest);

            if ($leaveBalance) {
                $pendingDaysToRelease = min($leaveRequest->total_days, $leaveBalance->days_pending);
                if ($pendingDaysToRelease > 0) {
                    $leaveBalance->decrement('days_pending', $pendingDaysToRelease);
                }
            }
        });

        AuditLog::record('update', "Rejected leave request #{$leaveRequest->id} for user #{$leaveRequest->user_id}", $leaveRequest);
        HrNotification::notify($leaveRequest->user_id, 'leave_rejected', 'Leave Request Rejected',
            "Your leave request has been rejected by {$reviewer->name}.", route('leave.apply'));
    }

    private function ensurePending(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status !== 'pending') {
            throw new RuntimeException('Only pending leave requests can be reviewed.');
        }
    }

    private function leaveBalanceFor(LeaveRequest $leaveRequest): ?LeaveBalance
    {
        return LeaveBalance::query()
            ->where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', now()->year)
            ->lockForUpdate()
            ->first();
    }
}
    public function approve(LeaveRequest $leaveRequest, User $reviewer): void
    {
        $this->ensurePending($leaveRequest);

        DB::transaction(function () use ($leaveRequest, $reviewer) {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'approval_remarks' => null,
            ]);

            $leaveBalance = $this->leaveBalanceFor($leaveRequest);

            if ($leaveBalance) {
                $pendingDaysToRelease = min($leaveRequest->total_days, $leaveBalance->days_pending);

                if ($pendingDaysToRelease > 0) {
                    $leaveBalance->decrement('days_pending', $pendingDaysToRelease);
                }

                $leaveBalance->increment('days_taken', $pendingDaysToRelease);
            }
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $reviewer): void
    {
        $this->ensurePending($leaveRequest);

        DB::transaction(function () use ($leaveRequest, $reviewer) {
            $leaveRequest->update([
                'status' => 'rejected',
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'approval_remarks' => 'Rejected by '.$reviewer->name,
            ]);

            $leaveBalance = $this->leaveBalanceFor($leaveRequest);

            if ($leaveBalance) {
                $pendingDaysToRelease = min($leaveRequest->total_days, $leaveBalance->days_pending);

                if ($pendingDaysToRelease > 0) {
                    $leaveBalance->decrement('days_pending', $pendingDaysToRelease);
                }
            }
        });
    }

    private function ensurePending(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status !== 'pending') {
            throw new RuntimeException('Only pending leave requests can be reviewed.');
        }
    }

    private function leaveBalanceFor(LeaveRequest $leaveRequest): ?LeaveBalance
    {
        return LeaveBalance::query()
            ->where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', now()->year)
            ->lockForUpdate()
            ->first();
    }
}
