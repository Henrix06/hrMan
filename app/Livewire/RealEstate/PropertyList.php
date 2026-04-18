<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\Property;
use Livewire\Component;
use Livewire\WithPagination;

class PropertyList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $type = '';
    public string $address = '';
    public string $city = '';
    public string $country = 'ZA';
    public int $units = 1;
    public string $market_value = '';
    public string $status = 'available';
    public string $description = '';

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
        $this->reset(['editingId', 'name', 'type', 'address', 'city', 'description', 'market_value']);
        $this->country = 'ZA';
        $this->units = 1;
        $this->status = 'available';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $property = Property::findOrFail($id);
        $this->editingId = $id;
        $this->name = $property->name;
        $this->type = $property->type;
        $this->address = $property->address;
        $this->city = $property->city;
        $this->country = $property->country;
        $this->units = $property->units;
        $this->market_value = (string) ($property->market_value ?? '');
        $this->status = $property->status;
        $this->description = $property->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('real_estate.manage_leases');

        $data = $this->validate([
            'name'         => 'required|string|max:200',
            'type'         => 'required|in:residential,commercial,industrial',
            'address'      => 'required|string|max:500',
            'city'         => 'required|string|max:100',
            'country'      => 'nullable|string|max:10',
            'units'        => 'required|integer|min:1',
            'market_value' => 'nullable|numeric|min:0',
            'status'       => 'required|in:available,partially_let,fully_let,under_maintenance,inactive',
            'description'  => 'nullable|string|max:2000',
        ]);

        if ($this->editingId) {
            $property = Property::findOrFail($this->editingId);
            $property->update($data);
            session()->flash('message', 'Property updated.');
        } else {
            Property::create(array_merge($data, [
                'reference'  => Property::generateReference('PROP'),
                'tenant_id'  => auth()->id(),
                'created_by' => auth()->id(),
            ]));
            session()->flash('message', 'Property created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $properties = Property::with(['leases' => fn($q) => $q->where('status', 'active')])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhere('city', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.real-estate.property-list', compact('properties'));
    }
}
