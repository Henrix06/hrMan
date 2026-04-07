<?php

namespace App\Livewire\Employees;

use App\Actions\Employees\UpdateEmployeeRecord;
use App\Models\User;
use App\Support\Employees\EmployeeReferenceData;
use Livewire\Component;

class EditEmployee extends Component
{
    public User $employee;
    public $name = '';
    public $email = '';
    public $password = '';
    public $employee_id = '';
    public $manager_id = '';
    public $hire_date = '';
    public $status = '';
    public $base_salary = '';
    public $department = '';
    public $position = '';
    public $phone = '';
    public $role = '';

    public function mount(User $employee): void
    {
        abort_unless(auth()->user()?->can('edit employee'), 403);

        $this->employee = $employee;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->employee_id = $employee->employee_id;
        $this->manager_id = $employee->manager_id;
        $this->hire_date = $employee->hire_date?->format('Y-m-d') ?? '';
        $this->status = $employee->status;
        $this->base_salary = $employee->base_salary;
        $this->department = $employee->department;
        $this->position = $employee->position;
        $this->phone = $employee->phone;
        $this->role = $employee->roles->first()?->name ?? 'Employee';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$this->employee->id,
            'password' => 'nullable|min:8',
            'employee_id' => 'nullable|unique:users,employee_id,'.$this->employee->id,
            'manager_id' => 'nullable|exists:users,id',
            'hire_date' => 'nullable|date|before_or_equal:today',
            'status' => 'required|in:active,inactive,terminated,probation',
            'base_salary' => 'nullable|numeric|min:0',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
        ];
    }

    public function update(): void
    {
        $this->validate();

        app(UpdateEmployeeRecord::class)->execute($this->employee, [
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
        ]);

        session()->flash('message', 'Employee updated successfully.');

        $this->redirect(route('employees.index'), navigate: true);
    }

    public function getManagersProperty()
    {
        return app(EmployeeReferenceData::class)->managers($this->employee->id);
    }

    public function getRolesProperty()
    {
        return app(EmployeeReferenceData::class)->assignableRoles();
    }

    public function render()
    {
        return view('livewire.employees.edit');
    }
}
