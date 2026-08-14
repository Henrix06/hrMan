<?php

namespace Tests\Feature\Auth;

use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
    }

    public function test_signup_page_renders(): void
    {
        $resp = $this->get('/signup');
        $this->assertEquals(200, $resp->getStatusCode(), substr($resp->getContent(), 0, 800));
    }

    public function test_free_signup_provisions_workspace(): void
    {
        $provisioner = app(\App\Services\TenantProvisioner::class);
        $result = $provisioner->provision([
            'name'       => 'Test User',
            'email'      => 'freeuser@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'password123',
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

    public function test_paid_signup_flow_completes(): void
    {
        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Signup::class, ['plan' => 'professional'])
            ->set('name', 'Card User')
            ->set('email', 'card@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('company_name', 'Card Co')
            ->call('nextToPayment');

        $this->assertEquals(2, $component->get('step'));

        $component
            ->set('payment_method', 'card')
            ->set('card_name', 'Card User')
            ->set('card_number', '4242424242424242')
            ->set('card_expiry', '08/27')
            ->set('card_cvc', '123')
            ->call('submitPayment')
            ->assertHasNoErrors();

        $this->assertTrue($component->get('done'));

        $user = User::where('email', 'card@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->tenant_id);
        $this->assertTrue($user->hasRole('Super Admin'));
    }

    public function test_employee_id_is_unique_across_signups(): void
    {
        $provisioner = app(\App\Services\TenantProvisioner::class);
        foreach (['one@example.com', 'two@example.com'] as $email) {
            $provisioner->provision([
                'name' => ucfirst(explode('@', $email)[0]),
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
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