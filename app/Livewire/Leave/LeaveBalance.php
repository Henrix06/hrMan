<?php

namespace App\Livewire\Leave;

use App\Models\LeaveBalance as ModelsLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LeaveBalance extends Component
{
    public $selectedYear;
    public $availableYears = [];

    public function mount(): void
    {
        $currentYear = now()->year;
        $this->selectedYear = $currentYear;
        $this->availableYears = range($currentYear - 2, $currentYear + 1);
    }

    public function render()
    {
        $user = Auth::user();
        $currentYear = $this->selectedYear;

        if ($user->isHRAdmin() || $user->hasRole('Super Admin')) {
            $balances = ModelsLeaveBalance::with(['user', 'leaveType'])
                ->where('year', $currentYear)
                ->orderBy('user_id')
                ->get()
                ->groupBy('user_id');
        } else {
            $balances = ModelsLeaveBalance::with('leaveType')
                ->where('user_id', $user->id)
                ->where('year', $currentYear)
                ->get();
        }

        return view('livewire.leave.balance', [
            'balances' => $balances,
            'leaveTypes' => LeaveType::where('active', true)->get(),
        ]);
    }
}
