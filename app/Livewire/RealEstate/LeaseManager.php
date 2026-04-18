<?php

namespace App\Livewire\RealEstate;

use App\Exceptions\RealEstate\LeaseActivationException;
use App\Exceptions\RealEstate\LeaseCreationException;
use App\Models\RealEstate\Lease;
use App\Models\RealEstate\Property;
use App\Models\RealEstate\RealEstateTenant;
use App\Services\RealEstate\LeaseService;
use Livewire\Component;
use Livewire\WithPagination;

class LeaseManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showCreateModal = false;
    public bool $showTerminateModal = false;
    public ?int $terminatingId = null;
    public string $terminationReason = '';

    public string $property_id = '';
    public string $re_tenant_id = '';
    public string $account_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $monthly_rent = '';
    public string $deposit_amount = '0';
    public bool $deposit_paid = false;
    public int $payment_due_day = 1;
    public string $penalty_rate = '5';
    public int $grace_period_days = 5;

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('real_estate.manage_leases');
        $this->reset([
            'property_id', 're_tenant_id', 'account_id', 'start_date', 'end_date',
            'monthly_rent', 'terminationReason',
        ]);
        $this->deposit_amount = '0';
        $this->deposit_paid = false;
        $this->payment_due_day = 1;
        $this->penalty_rate = '5';
        $this->grace_period_days = 5;
        $this->showCreateModal = true;
    }

    public function saveLease(): void
    {
        $this->authorize('real_estate.manage_leases');

        $data = $this->validate([
            'property_id'       => 'required|exists:re_properties,id',
            're_tenant_id'      => 'required|exists:re_tenants,id',
            'account_id'        => 'nullable|exists:accounts,id',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'monthly_rent'      => 'required|numeric|min:0',
            'deposit_amount'    => 'required|numeric|min:0',
            'deposit_paid'      => 'boolean',
            'payment_due_day'   => 'required|integer|min:1|max:28',
            'penalty_rate'      => 'required|numeric|min:0|max:100',
            'grace_period_days' => 'required|integer|min:0',
        ]);

        try {
            app(LeaseService::class)->create($data, auth()->user());
            session()->flash('message', 'Lease created successfully.');
            $this->showCreateModal = false;
            $this->resetPage();
        } catch (LeaseCreationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function activateLease(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $lease = Lease::findOrFail($id);

        try {
            app(LeaseService::class)->activate($lease, auth()->user());
            session()->flash('message', 'Lease activated.');
        } catch (LeaseActivationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openTerminate(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $this->terminatingId = $id;
        $this->terminationReason = '';
        $this->showTerminateModal = true;
    }

    public function confirmTerminate(): void
    {
        $this->authorize('real_estate.manage_leases');

        $this->validate([
            'terminationReason' => 'required|string|min:5|max:1000',
        ]);

        $lease = Lease::findOrFail($this->terminatingId);
        app(LeaseService::class)->terminate($lease, $this->terminationReason, auth()->user());
        session()->flash('message', 'Lease terminated.');
        $this->showTerminateModal = false;
        $this->terminatingId = null;
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $leases = Lease::withoutGlobalScopes()
            ->with(['property', 'reTenant'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhereHas('property', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('reTenant', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $properties = Property::whereIn('status', ['available', 'partially_let'])
            ->get(['id', 'name', 'reference']);

        $tenants = RealEstateTenant::where('status', 'active')
            ->get(['id', 'name', 'reference']);

        return view('livewire.real-estate.lease-manager', compact('leases', 'properties', 'tenants'));
    }
}
