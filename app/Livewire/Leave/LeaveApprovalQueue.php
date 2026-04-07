<?php

namespace App\Livewire\Leave;

use App\Actions\Leave\ReviewLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class LeaveApprovalQueue extends Component
{
    use WithPagination;

    public $statusFilter = 'pending';

    protected $queryString = [
        'statusFilter' => ['except' => 'pending'],
    ];

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveLeave($leaveRequestId): void
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
        $user = Auth::user();

        if (!$this->canReviewLeave($user, $leaveRequest, 'approve leave', 'approve')) {
            return;
        }

        try {
            app(ReviewLeaveRequest::class)->approve($leaveRequest, $user);
            session()->flash('message', 'Leave request approved successfully.');
        } catch (RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function rejectLeave($leaveRequestId): void
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
        $user = Auth::user();

        if (!$this->canReviewLeave($user, $leaveRequest, 'reject leave', 'reject')) {
            return;
        }

        try {
            app(ReviewLeaveRequest::class)->reject($leaveRequest, $user);
            session()->flash('message', 'Leave request rejected.');
        } catch (RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();

        $query = LeaveRequest::with(['user:id,name,manager_id', 'leaveType:id,name', 'approvedBy:id,name'])
            ->orderByDesc('created_at');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if (!$user->hasRole(['Super Admin', 'HR Admin'])) {
            $subordinateIds = User::where('manager_id', $user->id)->pluck('id');
            $query->whereIn('user_id', $subordinateIds);
        }

        return view('livewire.leave.approval-queue', [
            'leaveRequests' => $query->paginate(15),
        ]);
    }

    private function canReviewLeave($user, LeaveRequest $leaveRequest, string $permission, string $action): bool
    {
        if (!$user->can($permission)) {
            session()->flash('error', "You do not have permission to {$action} leave requests.");
            return false;
        }

        if (!$user->hasRole(['Super Admin', 'HR Admin']) && $leaveRequest->user->manager_id !== $user->id) {
            session()->flash('error', 'You can only review leave requests from your subordinates.');
            return false;
        }

        return true;
    }
}
