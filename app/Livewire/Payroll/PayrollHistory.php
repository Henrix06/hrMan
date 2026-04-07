<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $payPeriodFilter = '';
    public $statusFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'payPeriodFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'payPeriodFilter', 'statusFilter', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function markAsPaid($payrollId): void
    {
        PayrollDetail::findOrFail($payrollId)->update(['status' => 'paid']);

        session()->flash('message', 'Payroll marked as paid.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = PayrollDetail::with(['user:id,name,email,employee_id', 'payslip:id,payroll_detail_id,payslip_number'])
            ->orderByDesc('pay_date');

        if ($this->search) {
            // Join is faster than whereHas for search
            $query->join('users', 'users.id', '=', 'payroll_details.user_id')
                ->where(fn($q) => $q
                    ->where('users.name', 'like', '%'.$this->search.'%')
                    ->orWhere('users.email', 'like', '%'.$this->search.'%')
                    ->orWhere('users.employee_id', 'like', '%'.$this->search.'%')
                )
                ->select('payroll_details.*');
        }

        if ($this->payPeriodFilter) {
            $query->where('pay_period', 'like', '%'.$this->payPeriodFilter.'%');
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if (!$user->isHRAdmin() && !$user->hasRole('Super Admin')) {
            $query->where('user_id', $user->id);
        }

        return view('livewire.payroll.history', [
            'payrollDetails' => $query->paginate($this->perPage),
            'payPeriods' => \Illuminate\Support\Facades\Cache::remember('payroll_periods', 300, fn() =>
                PayrollDetail::select('pay_period')
                    ->distinct()
                    ->orderByDesc('pay_period')
                    ->pluck('pay_period')
            ),
        ]);
    }
}
