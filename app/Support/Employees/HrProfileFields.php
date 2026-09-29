<?php

namespace App\Support\Employees;

class HrProfileFields
{
    /**
     * Every extended HR profile column on the users table.
     *
     * @var list<string>
     */
    public const FIELDS = [
        // Personal
        'date_of_birth', 'gender', 'national_id', 'nationality', 'marital_status',
        // Contact
        'personal_email', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
        // Emergency contact
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
        // Employment terms
        'employment_type', 'contract_start_date', 'contract_end_date', 'probation_end_date',
        'work_location', 'job_grade', 'pay_frequency',
        // Bank & tax
        'bank_name', 'bank_account_number', 'bank_branch', 'tax_number',
    ];

    /** @var list<string> */
    public const GENDERS = ['male', 'female', 'other', 'prefer_not_to_say'];

    /** @var list<string> */
    public const MARITAL_STATUSES = ['single', 'married', 'divorced', 'widowed', 'other'];

    /** @var list<string> */
    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contract', 'internship', 'temporary'];

    /** @var list<string> */
    public const PAY_FREQUENCIES = ['monthly', 'weekly', 'biweekly', 'daily', 'hourly'];

    /** @var list<string> */
    public const EMPLOYEE_STATUSES = ['active', 'probation', 'inactive', 'terminated'];

    /**
     * Pull just the HR profile values out of a submitted payload, blank strings becoming null.
     *
     * @return array<string, mixed>
     */
    public static function extract(array $data): array
    {
        $extracted = [];

        foreach (self::FIELDS as $field) {
            $value = $data[$field] ?? null;
            $extracted[$field] = self::blankToNull($value);
        }

        return $extracted;
    }

    /**
     * Build a value => label map for a select input, led by an empty "not specified" entry.
     *
     * @param  list<string>  $values
     * @return array<string, string>
     */
    public static function options(array $values, string $blank = 'Not specified'): array
    {
        $options = ['' => $blank];

        foreach ($values as $value) {
            $options[$value] = ucwords(str_replace('_', ' ', $value));
        }

        return $options;
    }

    public static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    private static function blankToNull(mixed $value): mixed
    {
        return (is_string($value) && trim($value) === '') ? null : $value;
    }
}
