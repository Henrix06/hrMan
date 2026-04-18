<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\Property;
use Livewire\Component;

class PropertyDetail extends Component
{
    public int $propertyId;

    public function render()
    {
        $this->authorize('real_estate.view');

        $property = Property::with([
            'leases.reTenant',
            'maintenanceRequests' => fn($q) => $q->whereIn('status', ['open', 'in_progress'])->orderByDesc('created_at'),
        ])->findOrFail($this->propertyId);

        return view('livewire.real-estate.property-detail', compact('property'));
    }
}
