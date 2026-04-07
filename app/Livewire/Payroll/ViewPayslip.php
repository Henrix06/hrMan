<?php

namespace App\Livewire\Payroll;

use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ViewPayslip extends Component
{
    public ?Payslip $payslip = null;

    public function mount($payslipId = null): void
    {
        $user = Auth::user();

        if ($payslipId) {
            $payslip = Payslip::with(['payrollDetail', 'user'])->findOrFail($payslipId);

            if (!$user->isHRAdmin() && !$user->hasRole('Super Admin') && $payslip->user_id !== $user->id) {
                abort(403, 'Unauthorized access to payslip.');
            }

            $this->payslip = $payslip;
        }
    }

    public function downloadPayslip(): void
    {
        session()->flash('message', 'Payslip download feature coming soon.');
    }

    public function sendToEmployee(): void
    {
        if (!Auth::user()->can('view all payslips')) {
            session()->flash('error', 'You do not have permission to send payslips.');
            return;
        }

        if ($this->payslip) {
            $this->payslip->update([
                'sent_to_employee' => true,
                'sent_at' => now(),
            ]);

            session()->flash('message', 'Payslip sent to employee successfully.');
        }
    }

    public function render()
    {
        return view('livewire.payroll.payslip');
    }
}
