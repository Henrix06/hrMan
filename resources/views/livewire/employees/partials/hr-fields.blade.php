@php
    use App\Support\Employees\HrProfileFields as HR;
@endphp

{{-- Personal details --}}
<div class="md:col-span-2 mt-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Personal Details</h3>
</div>

<x-hr-field label="Date of Birth" model="date_of_birth" type="date" />
<x-hr-field label="Gender" model="gender" :options="HR::options(HR::GENDERS)" />
<x-hr-field label="National ID / Passport" model="national_id" placeholder="ID or passport number" />
<x-hr-field label="Nationality" model="nationality" placeholder="e.g. South African" />
<x-hr-field label="Marital Status" model="marital_status" :options="HR::options(HR::MARITAL_STATUSES)" />

{{-- Contact information --}}
<div class="md:col-span-2 mt-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Contact Information</h3>
</div>

<x-hr-field label="Personal Email" model="personal_email" type="email" placeholder="name@personal.com" />
<x-hr-field label="Address Line 1" model="address_line1" placeholder="Street address" />
<x-hr-field label="Address Line 2" model="address_line2" placeholder="Apartment, suite, etc." />
<x-hr-field label="City" model="city" />
<x-hr-field label="Province / State" model="state" />
<x-hr-field label="Postal Code" model="postal_code" />
<x-hr-field label="Country" model="country" />

{{-- Emergency contact --}}
<div class="md:col-span-2 mt-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Emergency Contact</h3>
</div>

<x-hr-field label="Contact Name" model="emergency_contact_name" />
<x-hr-field label="Relationship" model="emergency_contact_relationship" placeholder="e.g. Spouse, Parent" />
<x-hr-field label="Contact Phone" model="emergency_contact_phone" />

{{-- Employment terms --}}
<div class="md:col-span-2 mt-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Employment Terms</h3>
</div>

<x-hr-field label="Employment Type" model="employment_type" :options="HR::options(HR::EMPLOYMENT_TYPES)" />
<x-hr-field label="Work Location" model="work_location" placeholder="Office, Remote, Hybrid" />
<x-hr-field label="Contract Start" model="contract_start_date" type="date" />
<x-hr-field label="Contract End" model="contract_end_date" type="date" />
<x-hr-field label="Probation Ends" model="probation_end_date" type="date" />
<x-hr-field label="Job Grade / Level" model="job_grade" placeholder="e.g. L3, Band C" />
<x-hr-field label="Pay Frequency" model="pay_frequency" :options="HR::options(HR::PAY_FREQUENCIES)" />

{{-- Payment & tax --}}
<div class="md:col-span-2 mt-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Payment &amp; Tax</h3>
</div>

<x-hr-field label="Bank Name" model="bank_name" />
<x-hr-field label="Bank Branch" model="bank_branch" />
<x-hr-field label="Account Number" model="bank_account_number" />
<x-hr-field label="Tax Number" model="tax_number" />
