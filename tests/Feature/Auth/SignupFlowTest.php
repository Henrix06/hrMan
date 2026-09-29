<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Signup;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SignupFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
    }

    public function test_signup_page_renders(): void
    {
        $resp = $this->get('/signup');
        $this->assertEquals(200, $resp->getStatusCode(), substr($resp->getContent(), 0, 800));
    }

    public function test_free_signup_provisions_workspace(): void
    {
        $provisioner = app(TenantProvisioner::class);
        $result = $provisioner->provision([
            'name'       => 'Test User',
            'email'      => 'freeuser@example.com',
            'password'   => 'password123',
            'company_name' => 'Free Company',
            'plan'       => 'free',
            'billing_cycle' => 'monthly',
            'payment_method' => ['type' => 'none'],
        ]);

        $this->assertNotNull($result['user']);
        $this->assertTrue($result['user']->hasVerifiedEmail());
        $this->assertEquals('active', $result['user']->status);

        $moduleKeys = TenantModule::where('tenant_id', $result['tenant']->id)->pluck('module_key')->all();
        $this->assertContains('employee_management', $moduleKeys);
    }

    public function test_signup_creates_company_and_logs_in(): void
    {
        Livewire::test(Signup::class)
            ->set('name', 'Owner User')
            ->set('email', 'owner@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('company_name', 'Acme Corp')
            ->call('signup')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'owner@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->tenant_id);
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('tenants', [
            'id'    => $user->tenant_id,
            'name'  => 'Acme Corp',
            'plan'  => 'free',
        ]);
    }

    public function test_paid_plan_signup_does_not_require_payment(): void
    {
        Livewire::test(Signup::class)
            ->set('plan', 'professional')
            ->set('name', 'Card User')
            ->set('email', 'card@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('company_name', 'Card Co')
            ->call('signup')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'card@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertEquals('professional', $user->tenant->plan);

        $moduleKeys = TenantModule::where('tenant_id', $user->tenant_id)->pluck('module_key')->all();
        $this->assertContains('payroll', $moduleKeys);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Livewire::test(Signup::class)
            ->set('name', 'First')
            ->set('email', 'dupe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('company_name', 'First Co')
            ->call('signup');

        Livewire::test(Signup::class)
            ->set('name', 'Second')
            ->set('email', 'dupe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('company_name', 'Second Co')
            ->call('signup')
            ->assertHasErrors(['email']);

        $this->assertEquals(1, User::where('email', 'dupe@example.com')->count());
    }

    public function test_employee_id_is_unique_across_signups(): void
    {
        $provisioner = app(TenantProvisioner::class);
        foreach (['one@example.com', 'two@example.com'] as $email) {
            $provisioner->provision([
                'name' => ucfirst(explode('@', $email)[0]),
                'email' => $email,
                'password' => 'password123',
                'company_name' => 'Company ' . $email,
                'plan' => 'free',
                'billing_cycle' => 'monthly',
                'payment_method' => ['type' => 'none'],
            ]);
        }

        $ids = User::pluck('employee_id')->all();
        $this->assertEquals(count($ids), count(array_unique($ids)), 'employee_ids must be unique');
    }
}
