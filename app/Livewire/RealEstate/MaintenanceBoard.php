<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\MaintenanceRequest;
use App\Models\User;
use App\Services\RealEstate\MaintenanceService;
use Livewire\Component;
use Livewire\WithPagination;

class MaintenanceBoard extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $priorityFilter = '';

    public bool $showAssignModal = false;
    public ?int $assigningId = null;
    public string $assigneeId = '';

    public bool $showResolveModal = false;
    public ?int $resolvingId = null;
    public string $resolutionNotes = '';
    public string $repairCost = '';

    protected $queryString = [
        'statusFilter'   => ['except' => ''],
        'priorityFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['statusFilter', 'priorityFilter'])) {
            $this->resetPage();
        }
    }

    public function openAssign(int $id): void
    {
        $this->authorize('real_estate.manage_maintenance');
        $this->assigningId = $id;
        $this->assigneeId = '';
        $this->showAssignModal = true;
    }

    public function confirmAssign(): void
    {
        $this->authorize('real_estate.manage_maintenance');

        $this->validate([
            'assigneeId' => 'required|exists:users,id',
        ]);

        $request = MaintenanceRequest::findOrFail($this->assigningId);
        $assignee = User::findOrFail($this->assigneeId);
        app(MaintenanceService::class)->assign($request, $assignee, auth()->user());
        session()->flash('message', 'Request assigned to ' . $assignee->name . '.');
        $this->showAssignModal = false;
        $this->assigningId = null;
    }

    public function openResolve(int $id): void
    {
        $this->authorize('real_estate.manage_maintenance');
        $this->resolvingId = $id;
        $this->resolutionNotes = '';
        $this->repairCost = '';
        $this->showResolveModal = true;
    }

    public function confirmResolve(): void
    {
        $this->authorize('real_estate.manage_maintenance');

        $this->validate([
            'resolutionNotes' => 'required|string|min:5|max:2000',
            'repairCost'      => 'nullable|numeric|min:0',
        ]);

        $request = MaintenanceRequest::findOrFail($this->resolvingId);
        app(MaintenanceService::class)->resolve(
            $request,
            $this->resolutionNotes,
            $this->repairCost !== '' ? (float) $this->repairCost : null,
            auth()->user(),
        );
        session()->flash('message', 'Request marked as resolved.');
        $this->showResolveModal = false;
        $this->resolvingId = null;
    }

    public function closeRequest(int $id): void
    {
        $this->authorize('real_estate.manage_maintenance');
        $request = MaintenanceRequest::findOrFail($id);
        app(MaintenanceService::class)->close($request, auth()->user());
        session()->flash('message', 'Request closed.');
    }

    public function cancelRequest(int $id): void
    {
        $this->authorize('real_estate.manage_maintenance');
        $request = MaintenanceRequest::findOrFail($id);
        app(MaintenanceService::class)->cancel($request, auth()->user());
        session()->flash('message', 'Request cancelled.');
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $requests = MaintenanceRequest::with(['property', 'reTenant', 'assignee'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn($q) => $q->where('priority', $this->priorityFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        $users = User::where('status', 'active')->get(['id', 'name']);

        return view('livewire.real-estate.maintenance-board', compact('requests', 'users'));
    }
}
