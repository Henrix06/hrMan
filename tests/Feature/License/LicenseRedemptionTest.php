<?php

namespace Tests\Feature\License;

use App\Exceptions\License\LicenseRedemptionException;
use App\Livewire\License\RedeemLicense;
use App\Models\LicenseCode;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\TenantProvisioner;
use Database\Seeders\LicenseCodeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LicenseRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
    }

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

    private function makeCode(array $overrides = []): LicenseCode
    {
        return LicenseCode::create(array_merge([
            'code' => 'TEST-CODE-0001',
            'plan' => 'professional',
            'duration_days' => 365,
            'max_redemptions' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_valid_code_upgrades_plan_modules_and_subscription(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->provisionCompany();
        $license = $this->makeCode();

        $redemption = app(LicenseService::class)->redeem('test-code-0001', $tenant, $user);

        $tenant->refresh();

        $this->assertEquals('professional', $tenant->plan);
        $this->assertNotNull($tenant->subscription_ends_at);
        $this->assertTrue($tenant->subscription_ends_at->isFuture());
        $this->assertNull($tenant->trial_ends_at, 'A licence supersedes the trial');
        $this->assertEquals(1, $license->fresh()->times_redeemed);
        $this->assertEquals('professional', $redemption->plan);

        $this->assertDatabaseHas('tenant_modules', ['tenant_id' => $tenant->id, 'module_key' => 'payroll', 'is_enabled' => true]);
        $this->assertDatabaseHas('tenant_modules', ['tenant_id' => $tenant->id, 'module_key' => 'chat', 'is_enabled' => false]);
        $this->assertDatabaseHas('license_redemptions', ['license_code_id' => $license->id, 'tenant_id' => $tenant->id]);
    }

    public function test_code_input_is_case_and_hyphen_insensitive(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['code' => 'UMSIZI-ABCD-1234-WXYZ']);

        $service = app(LicenseService::class);

        $this->assertNotNull($service->findByCode('umsizi-abcd-1234-wxyz'));
        $this->assertNotNull($service->findByCode('UMSIZIABCD1234WXYZ'));
        $this->assertNotNull($service->findByCode('  umsizi abcd 1234 wxyz '));
        $this->assertNull($service->findByCode('totally-wrong'));

        $service->redeem('umsiziabcd1234wxyz', $tenant);
        $this->assertEquals('professional', $tenant->fresh()->plan);
    }

    public function test_unknown_code_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $this->expectException(LicenseRedemptionException::class);
        app(LicenseService::class)->redeem('NOPE-NOPE-NOPE', $tenant);
    }

    public function test_inactive_code_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['is_active' => false]);

        $this->expectException(LicenseRedemptionException::class);
        app(LicenseService::class)->redeem('TEST-CODE-0001', $tenant);
    }

    public function test_expired_code_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['expires_at' => now()->subDay()]);

        $this->expectException(LicenseRedemptionException::class);
        app(LicenseService::class)->redeem('TEST-CODE-0001', $tenant);
    }

    public function test_exhausted_code_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['max_redemptions' => 1, 'times_redeemed' => 1]);

        $this->expectException(LicenseRedemptionException::class);
        app(LicenseService::class)->redeem('TEST-CODE-0001', $tenant);
    }

    public function test_a_company_cannot_redeem_the_same_code_twice(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['max_redemptions' => null]);

        $service = app(LicenseService::class);
        $service->redeem('TEST-CODE-0001', $tenant);

        $this->expectException(LicenseRedemptionException::class);
        $service->redeem('TEST-CODE-0001', $tenant);
    }

    public function test_unlimited_code_can_be_redeemed_by_several_companies(): void
    {
        ['tenant' => $first] = $this->provisionCompany('first@example.com');
        ['tenant' => $second] = $this->provisionCompany('second@example.com');
        $license = $this->makeCode(['max_redemptions' => null]);

        $this->assertNull($license->remainingRedemptions(), 'Unlimited codes report no remaining count');

        $service = app(LicenseService::class);
        $service->redeem('TEST-CODE-0001', $first);
        $service->redeem('TEST-CODE-0001', $second);

        $this->assertEquals('professional', $first->fresh()->plan);
        $this->assertEquals('professional', $second->fresh()->plan);
        $this->assertEquals(2, $license->fresh()->times_redeemed);
    }

    public function test_redeeming_a_second_code_extends_the_subscription(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['code' => 'TEST-CODE-0001', 'duration_days' => 30]);
        $this->makeCode(['code' => 'TEST-CODE-0002', 'duration_days' => 30]);

        $service = app(LicenseService::class);
        $service->redeem('TEST-CODE-0001', $tenant);
        $service->redeem('TEST-CODE-0002', $tenant);

        $days = now()->diffInDays($tenant->fresh()->subscription_ends_at);

        $this->assertGreaterThanOrEqual(59, $days);
        $this->assertLessThanOrEqual(60, $days);
    }

    public function test_unknown_plan_on_code_is_rejected(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();
        $this->makeCode(['plan' => 'legacy_plan']);

        $this->expectException(LicenseRedemptionException::class);
        app(LicenseService::class)->redeem('TEST-CODE-0001', $tenant);
    }

    public function test_generated_codes_are_unique_and_redeemable(): void
    {
        $codes = collect(range(1, 25))->map(fn () => LicenseCode::generate());

        $this->assertEquals($codes->count(), $codes->unique()->count());

        $this->assertMatchesRegularExpression('/^UMSIZI-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codes->first());

        $license = $this->makeCode(['code' => $codes->first(), 'max_redemptions' => 1]);
        $this->assertTrue($license->isRedeemable());
    }

    public function test_license_generate_command_creates_a_redeemable_code(): void
    {
        $this->artisan('license:generate', [
            '--plan' => 'starter',
            '--days' => 90,
            '--count' => 2,
        ])->assertSuccessful();

        $this->assertEquals(2, LicenseCode::where('plan', 'starter')->count());

        ['tenant' => $tenant] = $this->provisionCompany();
        $code = LicenseCode::where('plan', 'starter')->first();

        app(LicenseService::class)->redeem($code->code, $tenant);

        $this->assertEquals('starter', $tenant->fresh()->plan);
    }

    public function test_livewire_page_redeems_a_code(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->provisionCompany();
        $this->makeCode();

        Livewire::actingAs($user)
            ->test(RedeemLicense::class)
            ->set('code', 'TEST-CODE-0001')
            ->call('redeem')
            ->assertSet('error_message', null)
            ->assertSet('code', '')
            ->assertSee('Licence applied');

        $this->assertEquals('professional', $tenant->fresh()->plan);
    }

    public function test_livewire_page_shows_an_error_for_a_bad_code(): void
    {
        ['user' => $user] = $this->provisionCompany();

        Livewire::actingAs($user)
            ->test(RedeemLicense::class)
            ->set('code', 'BAD-CODE')
            ->call('redeem')
            ->assertSet('status_message', null)
            ->assertSee('could not be found');
    }

    public function test_licence_page_requires_a_super_admin(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner)->get(route('license.index'))->assertOk();

        $employee = User::factory()->create(['tenant_id' => $tenant->id]);
        $employee->assignRole('Employee');

        $this->actingAs($employee)->get(route('license.index'))->assertForbidden();
    }

    public function test_seeded_demo_code_unlocks_the_professional_plan(): void
    {
        $this->artisan('db:seed', ['--class' => LicenseCodeSeeder::class]);

        ['tenant' => $tenant] = $this->provisionCompany();

        app(LicenseService::class)->redeem('UMSIZI-DEMO-PRO-2026', $tenant);

        $this->assertEquals('professional', $tenant->fresh()->plan);
    }
}
