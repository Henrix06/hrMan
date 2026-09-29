<?php

namespace Tests\Feature\Employees;

use App\Livewire\Employees\CreateEmployee;
use App\Livewire\Employees\EditEmployee;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\Employees\HrProfileFields;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
    }

    /**
     * @return array{tenant: Tenant, user: User}
     */
    private function provisionCompany(string $email = 'owner@example.com'): array
    {
        return app(TenantProvisioner::class)->provision([
            'name' => 'Owner User',
            'email' => $email,
            'password' => 'password123',
            'company_name' => 'Acme '.$email,
            'plan' => 'free',
            'billing_cycle' => 'monthly',
            'payment_method' => ['type' => 'none'],
        ]);
    }

    private function makeEmployee(int $tenantId, string $email = 'staff@example.com'): User
    {
        $employee = User::create([
            'name' => 'Staff Member',
            'email' => $email,
            'password' => 'password123',
            'tenant_id' => $tenantId,
            'status' => 'active',
        ]);

        $employee->assignRole('Employee');

        return $employee;
    }

    public function test_every_hr_field_is_mass_assignable(): void
    {
        $model = new User;

        foreach (HrProfileFields::FIELDS as $field) {
            $this->assertTrue($model->isFillable($field), "{$field} must be mass assignable on User");
        }
    }

    public function test_creating_an_employee_persists_the_full_hr_record(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(CreateEmployee::class)
            ->set('name', 'New Hire')
            ->set('email', 'newhire@example.com')
            ->set('password', 'password123')
            ->set('status', 'active')
            ->set('role', 'Employee')
            ->set('date_of_birth', '1994-03-12')
            ->set('gender', 'female')
            ->set('national_id', '9001015800083')
            ->set('nationality', 'South African')
            ->set('marital_status', 'married')
            ->set('personal_email', 'newhire@personal.com')
            ->set('address_line1', '12 Long Street')
            ->set('city', 'Cape Town')
            ->set('state', 'Western Cape')
            ->set('postal_code', '8001')
            ->set('country', 'South Africa')
            ->set('emergency_contact_name', 'Jane Doe')
            ->set('emergency_contact_relationship', 'Sister')
            ->set('emergency_contact_phone', '+27 82 000 0000')
            ->set('employment_type', 'full_time')
            ->set('contract_start_date', '2026-01-15')
            ->set('probation_end_date', '2026-04-15')
            ->set('work_location', 'Hybrid')
            ->set('job_grade', 'L2')
            ->set('pay_frequency', 'monthly')
            ->set('bank_name', 'First National Bank')
            ->set('bank_branch', '250655')
            ->set('bank_account_number', '6212345678')
            ->set('tax_number', 'TAX-99887766')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('employees.index'));

        $employee = User::where('email', 'newhire@example.com')->first();

        $this->assertNotNull($employee);
        $this->assertEquals('female', $employee->gender);
        $this->assertEquals('1994-03-12', $employee->date_of_birth->format('Y-m-d'));
        $this->assertEquals('South African', $employee->nationality);
        $this->assertEquals('12 Long Street', $employee->address_line1);
        $this->assertEquals('8001', $employee->postal_code);
        $this->assertEquals('Jane Doe', $employee->emergency_contact_name);
        $this->assertEquals('full_time', $employee->employment_type);
        $this->assertEquals('2026-01-15', $employee->contract_start_date->format('Y-m-d'));
        $this->assertEquals('Hybrid', $employee->work_location);
        $this->assertEquals('TAX-99887766', $employee->tax_number);
    }

    public function test_editing_an_employee_persists_the_full_hr_record(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeEmployee($tenant->id);

        Livewire::actingAs($owner)
            ->test(EditEmployee::class, ['employee' => $employee])
            ->set('date_of_birth', '1988-11-02')
            ->set('gender', 'male')
            ->set('nationality', 'Zimbabwean')
            ->set('city', 'Harare')
            ->set('employment_type', 'contract')
            ->set('contract_start_date', '2026-02-01')
            ->set('contract_end_date', '2026-12-31')
            ->set('pay_frequency', 'monthly')
            ->set('bank_name', 'CBZ')
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('employees.index'));

        $employee->refresh();

        $this->assertEquals('male', $employee->gender);
        $this->assertEquals('Harare', $employee->city);
        $this->assertEquals('contract', $employee->employment_type);
        $this->assertEquals('2026-12-31', $employee->contract_end_date->format('Y-m-d'));
        $this->assertEquals('CBZ', $employee->bank_name);
    }

    public function test_edit_form_is_prefilled_with_existing_hr_values(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $employee = $this->makeEmployee($tenant->id);
        $employee->update([
            'date_of_birth' => '1990-07-21',
            'gender' => 'other',
            'national_id' => 'ID-123',
            'employment_type' => 'part_time',
            'city' => 'Durban',
        ]);

        Livewire::actingAs($owner)
            ->test(EditEmployee::class, ['employee' => $employee->fresh()])
            ->assertSet('date_of_birth', '1990-07-21')
            ->assertSet('gender', 'other')
            ->assertSet('national_id', 'ID-123')
            ->assertSet('employment_type', 'part_time')
            ->assertSet('city', 'Durban');
    }

    public function test_blank_hr_fields_are_stored_as_null(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(CreateEmployee::class)
            ->set('name', 'Sparse Record')
            ->set('email', 'sparse@example.com')
            ->set('password', 'password123')
            ->set('status', 'active')
            ->set('role', 'Employee')
            ->set('city', '   ')
            ->set('national_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $employee = User::where('email', 'sparse@example.com')->first();

        $this->assertNull($employee->city);
        $this->assertNull($employee->national_id);
    }

    public function test_hr_validation_rejects_bad_values(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(CreateEmployee::class)
            ->set('name', 'Bad Data')
            ->set('email', 'baddata@example.com')
            ->set('password', 'password123')
            ->set('status', 'active')
            ->set('role', 'Employee')
            ->set('date_of_birth', now()->addYear()->toDateString())
            ->set('gender', 'unspecified')
            ->set('personal_email', 'not-an-email')
            ->set('employment_type', 'freelance')
            ->set('pay_frequency', 'fortnightly')
            ->set('contract_start_date', '2026-06-01')
            ->set('contract_end_date', '2026-01-01')
            ->call('save')
            ->assertHasErrors([
                'date_of_birth', 'gender', 'personal_email',
                'employment_type', 'pay_frequency', 'contract_end_date',
            ]);

        $this->assertNull(User::where('email', 'baddata@example.com')->first());
    }

    public function test_profile_page_shows_the_captured_hr_information(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $employee = $this->makeEmployee($tenant->id, 'profile@example.com');
        $employee->update([
            'date_of_birth' => '1992-02-02',
            'gender' => 'female',
            'nationality' => 'Namibian',
            'position' => 'Accountant',
            'personal_email' => 'profile@personal.com',
            'address_line1' => '5 Church Street',
            'city' => 'Windhoek',
            'emergency_contact_name' => 'Mary Doe',
            'employment_type' => 'full_time',
            'work_location' => 'Remote',
            'bank_name' => 'Bank Windhoek',
            'tax_number' => 'TAX-1',
        ]);

        $this->actingAs($owner)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Employee ID')
            ->assertSee('Namibian')
            ->assertSee('5 Church Street')
            ->assertSee('Mary Doe')
            ->assertSee('Remote')
            ->assertSee('Bank Windhoek')
            ->assertSee('Accountant');
    }

    public function test_profile_cannot_be_opened_for_another_companys_employee(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        ['tenant' => $otherTenant] = $this->provisionCompany('other@example.com');

        $stranger = $this->makeEmployee($otherTenant->id, 'stranger@example.com');

        $this->actingAs($owner)
            ->get(route('employees.show', $stranger))
            ->assertNotFound();
    }

    public function test_employee_can_view_their_own_profile_but_not_others(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $self = $this->makeEmployee($tenant->id, 'self@example.com');
        $other = $this->makeEmployee($tenant->id, 'other@example.com');

        $this->actingAs($self)->get(route('employees.show', $self))->assertOk();
        $this->actingAs($self)->get(route('employees.show', $other))->assertForbidden();
    }

    public function test_derived_hr_helpers(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $employee = $this->makeEmployee($tenant->id);
        $employee->update([
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'hire_date' => now()->subYears(4)->toDateString(),
            'address_line1' => '1 Main Road',
            'city' => 'Gaborone',
            'country' => 'Botswana',
            'probation_end_date' => now()->addMonth()->toDateString(),
        ]);

        $employee->refresh();

        $this->assertEquals(30, $employee->age());
        $this->assertEquals(4, $employee->tenureInYears());
        $this->assertEquals('1 Main Road, Gaborone, Botswana', $employee->fullAddress());
        $this->assertTrue($employee->isOnProbation(), 'A future probation end date means probation is open');
    }
}
