<?php

namespace App\Livewire\Employees\Concerns;

use App\Models\User;
use App\Support\Employees\HrProfileFields;

trait WithHrProfileFields
{
    public ?string $date_of_birth = '';

    public ?string $gender = '';

    public ?string $national_id = '';

    public ?string $nationality = '';

    public ?string $marital_status = '';

    public ?string $personal_email = '';

    public ?string $address_line1 = '';

    public ?string $address_line2 = '';

    public ?string $city = '';

    public ?string $state = '';

    public ?string $postal_code = '';

    public ?string $country = '';

    public ?string $emergency_contact_name = '';

    public ?string $emergency_contact_relationship = '';

    public ?string $emergency_contact_phone = '';

    public ?string $employment_type = '';

    public ?string $contract_start_date = '';

    public ?string $contract_end_date = '';

    public ?string $probation_end_date = '';

    public ?string $work_location = '';

    public ?string $job_grade = '';

    public ?string $pay_frequency = '';

    public ?string $bank_name = '';

    public ?string $bank_account_number = '';

    public ?string $bank_branch = '';

    public ?string $tax_number = '';

    /**
     * @return array<string, string>
     */
    protected function hrProfileRules(): array
    {
        return [
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:'.implode(',', HrProfileFields::GENDERS),
            'national_id' => 'nullable|string|max:64',
            'nationality' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:'.implode(',', HrProfileFields::MARITAL_STATUSES),
            'personal_email' => 'nullable|email|max:255',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'employment_type' => 'nullable|in:'.implode(',', HrProfileFields::EMPLOYMENT_TYPES),
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'probation_end_date' => 'nullable|date',
            'work_location' => 'nullable|string|max:255',
            'job_grade' => 'nullable|string|max:64',
            'pay_frequency' => 'nullable|in:'.implode(',', HrProfileFields::PAY_FREQUENCIES),
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:64',
            'bank_branch' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:64',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function hrProfileData(): array
    {
        return HrProfileFields::extract($this->only(HrProfileFields::FIELDS));
    }

    /**
     * Seed the form fields from an existing employee record.
     */
    protected function fillHrProfileFrom(User $employee): void
    {
        foreach (HrProfileFields::FIELDS as $field) {
            $value = $employee->getAttribute($field);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            $this->{$field} = (string) ($value ?? '');
        }
    }
}
