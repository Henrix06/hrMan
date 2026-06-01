<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\Property;
use App\Models\RealEstate\PropertyUnit;
use Livewire\Component;
use Livewire\WithPagination;

class PropertyUnitList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $listingTypeFilter = '';
    public ?int $propertyIdFilter = null;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $propertyId = null;
    public string $unitNumber = '';
    public string $type = 'apartment';
    public int $bedrooms = 0;
    public int $bathrooms = 0;
    public ?string $squareMeters = null;
    public ?string $rentAmount = null;
    public ?string $salePrice = null;
    public string $listingType = 'rent';
    public string $status = 'available';
    public ?string $description = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'listingTypeFilter' => ['except' => ''],
        'propertyIdFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'listingTypeFilter', 'propertyIdFilter'])) {
            $this->resetPage();
        }
    }

    public function openCreate(?int $propertyId = null): void
    {
        $this->authorize('real_estate.manage_leases');
        $this->reset(['editingId', 'propertyId', 'unitNumber', 'type', 'bedrooms', 'bathrooms', 'squareMeters', 'rentAmount', 'salePrice', 'listingType', 'status', 'description']);
        $this->propertyId = $propertyId;
        $this->type = 'apartment';
        $this->listingType = 'rent';
        $this->status = 'available';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $unit = PropertyUnit::findOrFail($id);
        $this->editingId = $id;
        $this->propertyId = $unit->property_id;
        $this->unitNumber = $unit->unit_number;
        $this->type = $unit->type;
        $this->bedrooms = $unit->bedrooms;
        $this->bathrooms = $unit->bathrooms;
        $this->squareMeters = (string) ($unit->square_meters ?? '');
        $this->rentAmount = (string) ($unit->rent_amount ?? '');
        $this->salePrice = (string) ($unit->sale_price ?? '');
        $this->listingType = $unit->listing_type;
        $this->status = $unit->status;
        $this->description = $unit->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('real_estate.manage_leases');

        $data = $this->validate([
            'propertyId' => 'required|exists:re_properties,id',
            'unitNumber' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'squareMeters' => 'nullable|numeric|min:0',
            'rentAmount' => 'nullable|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'listingType' => 'required|in:rent,sale,both',
            'status' => 'required|in:available,occupied,under_maintenance,inactive',
            'description' => 'nullable|string|max:2000',
        ]);

        $saveData = [
            'property_id' => $data['propertyId'],
            'unit_number' => $data['unitNumber'],
            'type' => $data['type'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'square_meters' => $data['squareMeters'],
            'rent_amount' => $data['rentAmount'],
            'sale_price' => $data['salePrice'],
            'listing_type' => $data['listingType'],
            'status' => $data['status'],
            'description' => $data['description'],
        ];

        if ($this->editingId) {
            $unit = PropertyUnit::findOrFail($this->editingId);
            $unit->update($saveData);
            session()->flash('message', 'Unit updated.');
        } else {
            PropertyUnit::create(array_merge($saveData, [
                'created_by' => auth()->id(),
            ]));
            session()->flash('message', 'Unit created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $units = PropertyUnit::with(['property', 'agentAssignments' => fn($q) => $q->where('status', 'active')])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('unit_number', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhereHas('property', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->listingTypeFilter, fn($q) => $q->where('listing_type', $this->listingTypeFilter))
            ->when($this->propertyIdFilter, fn($q) => $q->where('property_id', $this->propertyIdFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $properties = Property::orderBy('name')->pluck('name', 'id');

        return view('livewire.real-estate.property-unit-list', compact('units', 'properties'));
    }
}
