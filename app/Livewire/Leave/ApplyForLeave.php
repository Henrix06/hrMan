<?php

namespace App\Livewire\Leave;

use App\Actions\Leave\SubmitLeaveRequest;
use App\Models\LeaveType;
use App\Support\Leave\BusinessDayCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class ApplyForLeave extends Component
{
    public $leave_type_id = '';
    public $start_date = '';
    public $end_date = '';
    public $reason = '';
    public $total_days = 0;

    protected function rules(): array
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ];
    }

    public function updatedStartDate(): void
    {
        $this->calculateTotalDays();
    }

    public function updatedEndDate(): void
    {
        $this->calculateTotalDays();
    }

    public function updatedLeaveTypeId(): void
    {
        $this->reset(['start_date', 'end_date', 'total_days']);
    }

    private function calculateTotalDays(): void
    {
        if ($this->start_date && $this->end_date) {
            $this->total_days = app(BusinessDayCalculator::class)
                ->count($this->start_date, $this->end_date);
        }
    }

    public function submit(): void
    {
        $this->validate();

        if ($this->total_days <= 0) {
            $this->addError('total_days', 'Total days must be greater than 0 (weekends excluded)');
            return;
        }

        try {
            app(SubmitLeaveRequest::class)->execute(Auth::user(), [
                'leave_type_id' => $this->leave_type_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'reason' => $this->reason,
            ]);
        } catch (RuntimeException $exception) {
            $this->addError('total_days', $exception->getMessage());
            return;
        }

        session()->flash('message', 'Leave request submitted successfully.');

        $this->reset(['leave_type_id', 'start_date', 'end_date', 'reason', 'total_days']);
    }

    public function getLeaveTypesProperty()
    {
        return LeaveType::where('active', true)->get();
    }

    public function getLeaveBalancesProperty(): \Illuminate\Support\Collection
    {
        return \App\Models\LeaveBalance::where('user_id', Auth::id())
            ->where('year', now()->year)
            ->with('leaveType')
            ->get()
            ->keyBy('leave_type_id');
    }

    public function render()
    {
        return view('livewire.leave.apply');
    }
}
