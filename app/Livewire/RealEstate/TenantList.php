<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\RealEstateTenant;
use Livewire\Component;
use Livewire\WithPagination;

class TenantList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $id_number = '';
    public string $type = 'individual';
    public string $address = '';
    public string $status = 'active';
    public string $notes = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter'   => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'typeFilter'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('real_estate.manage_leases');
        $this->reset(['editingId', 'name', 'email', 'phone', 'id_number', 'address', 'notes']);
        $this->type = 'individual';
        $this->status = 'active';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $tenant = RealEstateTenant::findOrFail($id);
        $this->editingId = $id;
        $this->name = $tenant->name;
        $this->email = $tenant->email ?? '';
        $this->phone = $tenant->phone ?? '';
        $this->id_number = $tenant->id_number ?? '';
        $this->type = $tenant->type;
        $this->address = $tenant->address ?? '';
        $this->status = $tenant->status;
        $this->notes = $tenant->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('real_estate.manage_leases');

        $data = $this->validate([
            'name'      => 'required|string|max:200',
            'email'     => 'nullable|email|max:200',
            'phone'     => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:100',
            'type'      => 'required|in:individual,company',
            'address'   => 'nullable|string|max:500',
            'status'    => 'required|in:active,inactive,blacklisted',
            'notes'     => 'nullable|string|max:2000',
        ]);

        if ($this->editingId) {
            $tenant = RealEstateTenant::findOrFail($this->editingId);
            $tenant->update($data);
            session()->flash('message', 'Tenant updated.');
        } else {
            RealEstateTenant::create(array_merge($data, [
                'reference'         => RealEstateTenant::generateReference('TEN'),
                'company_tenant_id' => auth()->id(),
                'created_by'        => auth()->id(),
            ]));
            session()->flash('message', 'Tenant created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $tenants = RealEstateTenant::when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.real-estate.tenant-list', compact('tenants'));
    }
}
