<?php

namespace App\Livewire\Employees;

use App\Actions\Employees\CreateEmployeeRecord;
use App\Livewire\Employees\Concerns\WithHrProfileFields;
use App\Support\Employees\EmployeeReferenceData;
use Livewire\Component;

class CreateEmployee extends Component
{
    use WithHrProfileFields;

    public $name = '';

    public $email = '';

    public $password = '';

    public $employee_id = '';

    public $manager_id = '';

    public $hire_date = '';

    public $status = 'probation';

    public $base_salary = '';

    public $department = '';

    public $position = '';

    public $phone = '';

    public $role = 'Employee';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('create employee'), 403);
    }

    protected function rules(): array
    {
        return array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'employee_id' => 'nullable|unique:users,employee_id',
            'manager_id' => 'nullable|exists:users,id',
            'hire_date' => 'nullable|date|before_or_equal:today',
            'status' => 'required|in:active,inactive,terminated,probation',
            'base_salary' => 'nullable|numeric|min:0',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
        ], $this->hrProfileRules());
    }

    public function save(): void
    {
        $this->validate();

        app(CreateEmployeeRecord::class)->execute(array_merge([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'employee_id' => $this->employee_id,
            'manager_id' => $this->manager_id ?: null,
            'hire_date' => $this->hire_date ?: null,
            'status' => $this->status,
            'base_salary' => $this->base_salary ?: null,
            'department' => $this->department,
            'position' => $this->position,
            'phone' => $this->phone,
            'role' => $this->role,
        ], $this->hrProfileData()));

        session()->flash('message', 'Employee created successfully.');

        $this->redirect(route('employees.index'), navigate: true);
    }

    public function getManagersProperty()
    {
        return app(EmployeeReferenceData::class)->managers();
    }

    public function getRolesProperty()
    {
        return app(EmployeeReferenceData::class)->assignableRoles();
    }

    public function render()
    {
        return view('livewire.employees.create');
    }
}
