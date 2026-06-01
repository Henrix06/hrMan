<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\AgentAssignment;
use App\Models\RealEstate\PropertyUnit;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AgentAssignmentManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $agentIdFilter = null;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $unitId = null;
    public ?int $agentId = null;
    public string $agentRole = 'agent';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $status = 'active';
    public ?string $notes = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'agentIdFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'agentIdFilter'])) {
            $this->resetPage();
        }
    }

    public function openCreate(?int $unitId = null): void
    {
        $this->authorize('real_estate.manage_leases');
        $this->reset(['editingId', 'unitId', 'agentId', 'agentRole', 'startDate', 'endDate', 'status', 'notes']);
        $this->unitId = $unitId;
        $this->agentRole = 'agent';
        $this->status = 'active';
        $this->startDate = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('real_estate.manage_leases');
        $assignment = AgentAssignment::findOrFail($id);
        $this->editingId = $id;
        $this->unitId = $assignment->unit_id;
        $this->agentId = $assignment->agent_id;
        $this->agentRole = $assignment->agent_role;
        $this->startDate = $assignment->start_date->toDateString();
        $this->endDate = $assignment->end_date?->toDateString();
        $this->status = $assignment->status;
        $this->notes = $assignment->notes;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('real_estate.manage_leases');

        $data = $this->validate([
            'unitId' => 'required|exists:re_property_units,id',
            'agentId' => 'required|exists:users,id',
            'agentRole' => 'required|in:agent,manager',
            'startDate' => 'required|date',
            'endDate' => 'nullable|date|after:startDate',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:2000',
        ]);

        $saveData = [
            'company_tenant_id' => auth()->id(),
            'unit_id' => $data['unitId'],
            'agent_id' => $data['agentId'],
            'assigned_by' => auth()->id(),
            'agent_role' => $data['agentRole'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ];

        if ($this->editingId) {
            $assignment = AgentAssignment::findOrFail($this->editingId);
            $assignment->update($saveData);
            session()->flash('message', 'Assignment updated.');
        } else {
            AgentAssignment::create($saveData);
            session()->flash('message', 'Assignment created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $assignments = AgentAssignment::with(['unit.property', 'agent', 'assignedBy'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhereHas('agent', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('unit', fn($q) => $q->where('unit_number', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->agentIdFilter, fn($q) => $q->where('agent_id', $this->agentIdFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $units = PropertyUnit::where('status', 'available')->orWhere('status', 'occupied')->orderBy('unit_number')->pluck('unit_number', 'id');
        $agents = User::orderBy('name')->pluck('name', 'id');

        return view('livewire.real-estate.agent-assignment-manager', compact('assignments', 'units', 'agents'));
    }
}
