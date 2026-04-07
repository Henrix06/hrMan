<?php

namespace App\Livewire\Departments;

use App\Models\AuditLog;
use App\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentList extends Component
{
    use WithPagination;

    public $search = '';
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $manager_id = null;
    public bool $active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:departments,name,' . ($this->editingId ?? 'NULL'),
            'code' => 'nullable|string|max:20|unique:departments,code,' . ($this->editingId ?? 'NULL'),
            'description' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:users,id',
            'active' => 'boolean',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'code', 'description', 'manager_id', 'active', 'editingId', 'editing']);
        $this->active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $dept = Department::findOrFail($id);
        $this->editingId = $id;
        $this->editing = true;
        $this->name = $dept->name;
        $this->code = $dept->code ?? '';
        $this->description = $dept->description ?? '';
        $this->manager_id = $dept->manager_id;
        $this->active = $dept->active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('manage settings');
        $data = $this->validate();

        if ($this->editing && $this->editingId) {
            $dept = Department::findOrFail($this->editingId);
            $old = $dept->toArray();
            $dept->update($data);
            AuditLog::record('update', "Updated department: {$dept->name}", $dept, $old, $dept->fresh()->toArray());
            session()->flash('message', 'Department updated.');
        } else {
            $dept = Department::create($data);
            AuditLog::record('create', "Created department: {$dept->name}", $dept);
            session()->flash('message', 'Department created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('manage settings');
        $dept = Department::findOrFail($id);
        AuditLog::record('delete', "Deleted department: {$dept->name}", $dept);
        $dept->delete();
        session()->flash('message', 'Department deleted.');
    }

    public function render()
    {
        $departments = Department::with('manager')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(10);

        $managers = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['Manager', 'HR Admin', 'Super Admin']))
            ->orderBy('name')->get();

        return view('livewire.departments.index', compact('departments', 'managers'));
    }
}
