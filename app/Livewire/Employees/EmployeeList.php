<?php

namespace App\Livewire\Employees;

use App\Models\User;
use App\Support\Employees\EmployeeFilters;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $departmentFilter = '';

    public $roleFilter = '';

    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
        'roleFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'departmentFilter', 'roleFilter', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function deleteEmployee($employeeId): void
    {
        if (! auth()->user()?->can('delete employee')) {
            session()->flash('error', 'You do not have permission to delete employees.');

            return;
        }

        $employee = User::findOrFail($employeeId);
        $employee->delete();

        session()->flash('message', 'Employee deleted successfully.');
    }

    public function render()
    {
        $filters = app(EmployeeFilters::class);
        $query = User::with(['manager:id,name', 'roles:id,name'])
            ->select(['id', 'name', 'email', 'employee_id', 'department', 'position', 'status', 'manager_id', 'created_at'])
            ->orderByDesc('created_at');

        $filters->apply($query, [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'department' => $this->departmentFilter,
            'role' => $this->roleFilter,
        ]);

        return view('livewire.employees.index', [
            'employees' => $query->paginate($this->perPage),
            'departments' => $filters->departments(),
        ]);
    }
}
