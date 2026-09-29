<?php

namespace App\Livewire\Employees;

use App\Models\User;
use App\Support\Employees\HrProfileFields;
use Livewire\Component;

class EmployeeProfile extends Component
{
    public User $employee;

    public function mount(User $employee): void
    {
        abort_unless(auth()->user()?->can('view all employees') || auth()->id() === $employee->id, 403);

        // Never expose another company's records by guessing an id.
        abort_unless($employee->tenant_id === auth()->user()?->tenant_id, 404);

        $this->employee = $employee->load(['manager:id,name', 'roles:id,name', 'department']);
    }

    /**
     * Everything captured about this employee, grouped for display.
     *
     * @return array<string, array<string, string>>
     */
    public function sections(): array
    {
        $employee = $this->employee;
        $label = fn (?string $value) => HrProfileFields::label($value);
        $date = fn ($value) => $value?->format('j M Y') ?? '—';

        return [
            'Employment' => [
                'Employee ID' => $employee->employee_id ?: '—',
                'Position' => $employee->position ?: '—',
                'Department' => $employee->department ?: ($employee->getRelation('department')?->name ?? '—'),
                'Reports To' => $employee->manager?->name ?? '—',
                'Role' => $employee->roles->pluck('name')->join(', ') ?: '—',
                'Status' => $label($employee->status),
                'Hire Date' => $date($employee->hire_date),
                'Tenure' => $employee->tenureInYears() !== null ? $employee->tenureInYears().' years' : '—',
                'Employment Type' => $label($employee->employment_type),
                'Work Location' => $employee->work_location ?: '—',
                'Job Grade' => $employee->job_grade ?: '—',
                'Contract Start' => $date($employee->contract_start_date),
                'Contract End' => $date($employee->contract_end_date),
                'Probation Ends' => $date($employee->probation_end_date),
                'On Probation' => $employee->isOnProbation() ? 'Yes' : 'No',
            ],
            'Personal' => [
                'Date of Birth' => $date($employee->date_of_birth),
                'Age' => $employee->age() !== null ? $employee->age().' years' : '—',
                'Gender' => $label($employee->gender),
                'Nationality' => $employee->nationality ?: '—',
                'National ID' => $employee->national_id ?: '—',
                'Marital Status' => $label($employee->marital_status),
            ],
            'Contact' => [
                'Work Email' => $employee->email ?: '—',
                'Personal Email' => $employee->personal_email ?: '—',
                'Phone' => $employee->phone ?: '—',
                'Address' => $employee->fullAddress() ?? '—',
            ],
            'Emergency Contact' => [
                'Name' => $employee->emergency_contact_name ?: '—',
                'Relationship' => $employee->emergency_contact_relationship ?: '—',
                'Phone' => $employee->emergency_contact_phone ?: '—',
            ],
            'Payment & Tax' => [
                'Base Salary' => $employee->base_salary !== null ? number_format((float) $employee->base_salary, 2) : '—',
                'Pay Frequency' => $label($employee->pay_frequency),
                'Bank' => $employee->bank_name ?: '—',
                'Branch' => $employee->bank_branch ?: '—',
                'Account Number' => $employee->bank_account_number ?: '—',
                'Tax Number' => $employee->tax_number ?: '—',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.employees.profile', [
            'sections' => $this->sections(),
        ]);
    }
}
