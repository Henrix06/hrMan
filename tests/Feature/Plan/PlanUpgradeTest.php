<?php

namespace Tests\Feature\Plan;

use App\Exceptions\Plan\PlanChangeException;
use App\Livewire\Plan\ManagePlan;
use App\Models\LicenseCode;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\PlanService;
use App\Services\TenantProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanUpgradeTest extends TestCase
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
    private function provisionCompany(string $email = 'owner@example.com', bool $trialExpired = false): array
    {
        $result = app(TenantProvisioner::class)->provision([
            'name' => 'Owner User',
            'email' => $email,
            'password' => 'password123',
            'company_name' => 'Acme '.$email,
            'plan' => 'free',
            'billing_cycle' => 'monthly',
            'payment_method' => ['type' => 'none'],
        ]);

        if ($trialExpired) {
            $result['tenant']->trial_ends_at = now()->subDay();
            $result['tenant']->save();
        }

        return $result;
    }

    private function moduleEnabled(int $tenantId, string $key): bool
    {
        return (bool) TenantModule::where('tenant_id', $tenantId)
            ->where('module_key', $key)
            ->value('is_enabled');
    }

    public function test_upgrading_without_entitlement_enables_modules_and_starts_a_trial(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany(trialExpired: true);

        $result = app(PlanService::class)->switchPlan($tenant, 'professional');

        $tenant->refresh();

        $this->assertEquals('professional', $tenant->plan);
        $this->assertTrue($result['started_trial']);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertTrue($tenant->trial_ends_at->isFuture());

        $this->assertTrue($this->moduleEnabled($tenant->id, 'payroll'));
        $this->assertTrue($this->moduleEnabled($tenant->id, 'task_management'));
        $this->assertFalse($this->moduleEnabled($tenant->id, 'chat'));

        $this->assertContains('payroll', $result['added_modules']);
        $this->assertContains('leave_management', $result['added_modules']);
        $this->assertNotContains('chat', $result['added_modules']);
        $this->assertSame([], $result['removed_modules'], 'The Free plan is a subset of Professional');
    }

    public function test_upgrading_keeps_a_live_trial_window(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $originalTrialEnd = $tenant->trial_ends_at->copy();

        $result = app(PlanService::class)->switchPlan($tenant, 'starter');

        $this->assertFalse($result['started_trial']);
        $this->assertEquals('starter', $tenant->fresh()->plan);
        $this->assertTrue($tenant->fresh()->trial_ends_at->equalTo($originalTrialEnd), 'An existing trial is not restarted');
    }

    public function test_upgrading_keeps_a_paid_subscription_window(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->provisionCompany();

        LicenseCode::create([
            'code' => 'PAID-CODE-0001',
            'plan' => 'starter',
            'duration_days' => 365,
            'max_redemptions' => 1,
        ]);

        app(LicenseService::class)->redeem('PAID-CODE-0001', $tenant, $user);
        $paidUntil = $tenant->fresh()->subscription_ends_at->copy();

        app(PlanService::class)->switchPlan($tenant, 'professional');

        $this->assertEquals('professional', $tenant->fresh()->plan);
        $this->assertTrue($tenant->fresh()->subscription_ends_at->equalTo($paidUntil), 'Paid time is preserved on upgrade');
        $this->assertTrue($this->moduleEnabled($tenant->id, 'payroll'));
    }

    public function test_downgrading_to_free_clears_dates_and_disables_modules(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany(trialExpired: true);

        app(PlanService::class)->switchPlan($tenant, 'professional');
        $this->assertTrue($this->moduleEnabled($tenant->id, 'payroll'));

        $result = app(PlanService::class)->switchPlan($tenant, 'free');

        $tenant->refresh();

        $this->assertEquals('free', $tenant->plan);
        $this->assertNull($tenant->trial_ends_at);
        $this->assertNull($tenant->subscription_ends_at);
        $this->assertFalse($this->moduleEnabled($tenant->id, 'payroll'));
        $this->assertFalse($this->moduleEnabled($tenant->id, 'leave_management'));
        $this->assertTrue($this->moduleEnabled($tenant->id, 'employee_management'));

        $this->assertContains('payroll', $result['removed_modules']);
    }

    public function test_switching_to_the_current_plan_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $this->expectException(PlanChangeException::class);
        app(PlanService::class)->switchPlan($tenant, 'free');
    }

    public function test_unknown_plan_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $this->expectException(PlanChangeException::class);
        app(PlanService::class)->switchPlan($tenant, 'platinum');
    }

    public function test_contact_sales_plan_cannot_be_self_switched(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $this->expectException(PlanChangeException::class);
        app(PlanService::class)->switchPlan($tenant, 'enterprise');
    }

    public function test_billing_cycle_is_stored_with_the_switch(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        app(PlanService::class)->switchPlan($tenant, 'professional', null, 'yearly');

        $this->assertEquals('yearly', $tenant->fresh()->billing_cycle);
    }

    public function test_module_diff_reports_gained_and_lost_modules(): void
    {
        $downgrade = app(PlanService::class)->moduleDiff('professional', 'free');

        $this->assertSame([], $downgrade['added']);
        $this->assertContains('payroll', $downgrade['removed']);
        $this->assertContains('task_management', $downgrade['removed']);
        $this->assertNotContains('employee_management', $downgrade['removed']);

        $upgrade = app(PlanService::class)->moduleDiff('free', 'professional');

        $this->assertContains('payroll', $upgrade['added']);
        $this->assertSame([], $upgrade['removed']);
    }

    public function test_livewire_page_switches_plan(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany(trialExpired: true);

        Livewire::actingAs($owner)
            ->test(ManagePlan::class)
            ->call('confirm', 'professional')
            ->assertSet('confirmingPlan', 'professional')
            ->call('switchPlan')
            ->assertSet('confirmingPlan', null)
            ->assertSet('error_message', null)
            ->assertSee('Professional');

        $this->assertEquals('professional', $tenant->fresh()->plan);
        $this->assertTrue($this->moduleEnabled($tenant->id, 'payroll'));
    }

    public function test_livewire_page_can_cancel_a_switch(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(ManagePlan::class)
            ->call('confirm', 'professional')
            ->call('cancel')
            ->assertSet('confirmingPlan', null);

        $this->assertEquals('free', $tenant->fresh()->plan);
    }

    public function test_livewire_page_reports_a_rejected_switch(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(ManagePlan::class)
            ->call('confirm', 'free')
            ->call('switchPlan')
            ->assertSee('already on that plan');

        $this->assertEquals('free', $tenant->fresh()->plan);
    }

    public function test_plan_page_requires_a_super_admin(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner)->get(route('plan.index'))->assertOk();

        $employee = User::factory()->create(['tenant_id' => $tenant->id]);
        $employee->assignRole('Employee');

        $this->actingAs($employee)->get(route('plan.index'))->assertForbidden();
    }

    public function test_module_disabled_page_points_super_admins_at_the_plan_page(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $html = view('errors.module-disabled', ['moduleKey' => 'payroll'])->render();

        $this->assertStringContainsString(route('plan.index'), $html);
    }
}
