<?php

namespace App\Livewire\Assets;

use App\Models\AssetAssignment;
use Livewire\Component;
use Livewire\WithPagination;

class MyAssets extends Component
{
    use WithPagination;

    public string $tab = 'active'; // active | history

    public function render()
    {
        $userId = auth()->id();

        $query = AssetAssignment::with(['asset', 'assignedBy:id,name'])
            ->where('user_id', $userId);

        if ($this->tab === 'active') {
            $query->where('status', 'active');
        } else {
            $query->where('status', 'returned')->orderByDesc('actual_return_date');
        }

        return view('livewire.assets.my-assets', [
            'assignments' => $query->orderByDesc('assigned_date')->paginate(10),
        ]);
    }
}
