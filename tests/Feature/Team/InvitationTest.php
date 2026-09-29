<?php

namespace Tests\Feature\Team;

use App\Exceptions\Invitation\InvitationException;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Team\TeamMembers;
use App\Mail\TeamInvitation;
use App\Models\Invitation;
use App\Models\LeaveBalance;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\TenantProvisioner;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', [
            '--class' => RolePermissionSeeder::class,
        ]);
        $this->artisan('db:seed', [
            '--class' => LeaveTypeSeeder::class,
        ]);
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

    private function makeInvitation(
        Tenant $tenant,
        User $inviter,
        string $email = 'newbie@example.com',
        string $role = 'Employee',
        ?string $plainToken = null,
        ?string $expiresAt = null,
        ?string $acceptedAt = null,
    ): Invitation {
        $plainToken ??= 'test-token-'.Str::ulid();

        return Invitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'name' => null,
            'role' => $role,
            'token' => hash('sha256', $plainToken),
            'invited_by' => $inviter->id,
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'accepted_at' => $acceptedAt,
        ]);
    }

    public function test_super_admin_can_invite_a_teammate(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $invitation = app(InvitationService::class)->invite($tenant, [
            'email' => 'Newbie@Example.com',
            'name' => 'New Bie',
            'role' => 'Manager',
        ], $owner);

        $this->assertEquals('newbie@example.com', $invitation->email, 'Emails are normalised to lowercase');
        $this->assertEquals('Manager', $invitation->role);
        $this->assertEquals($tenant->id, $invitation->tenant_id);
        $this->assertEquals($owner->id, $invitation->invited_by);
        $this->assertTrue($invitation->isPending());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $invitation->token, 'The raw token is never stored');
        $this->assertTrue($invitation->expires_at->isFuture());

        Mail::assertSent(TeamInvitation::class, fn (TeamInvitation $mail) => $mail->hasTo('newbie@example.com'));
    }

    public function test_emailed_link_resolves_to_its_invitation(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitations = app(InvitationService::class);

        $invitations->invite($tenant, ['email' => 'newbie@example.com', 'role' => 'Employee'], $owner);

        $mailable = Mail::sent(TeamInvitation::class)->first();
        $this->assertNotNull($mailable);

        $token = basename(parse_url($mailable->acceptUrl, PHP_URL_PATH));
        $stored = Invitation::first()->token;

        $this->assertNotSame($stored, $token, 'The emailed token differs from the stored hash');

        $resolved = $invitations->findByToken($token);

        $this->assertNotNull($resolved);
        $this->assertSame($stored, $resolved->token);
        $this->assertEquals('newbie@example.com', $resolved->email);
    }

    public function test_cannot_invite_an_existing_account(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->expectException(InvitationException::class);

        app(InvitationService::class)->invite($tenant, [
            'email' => $owner->email,
            'role' => 'Employee',
        ], $owner);
    }

    public function test_cannot_invite_the_same_email_twice_while_pending(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitations = app(InvitationService::class);

        $invitations->invite($tenant, ['email' => 'newbie@example.com', 'role' => 'Employee'], $owner);

        $this->expectException(InvitationException::class);

        $invitations->invite($tenant, ['email' => 'newbie@example.com', 'role' => 'Manager'], $owner);
    }

    public function test_cannot_invite_with_an_unknown_role(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->expectException(InvitationException::class);

        app(InvitationService::class)->invite($tenant, [
            'email' => 'newbie@example.com',
            'role' => 'Wizard',
        ], $owner);
    }

    public function test_accepting_creates_a_verified_teammate_with_a_role_and_leave_balances(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitation = $this->makeInvitation($tenant, $owner, 'newbie@example.com', 'Manager');

        $user = app(InvitationService::class)->accept($invitation, [
            'name' => 'New Bie',
            'password' => 'password123',
        ]);

        $this->assertEquals($tenant->id, $user->tenant_id);
        $this->assertEquals('newbie@example.com', $user->email);
        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('Manager'));
        $this->assertNotNull($user->employee_id);

        $this->assertGreaterThan(0, LeaveBalance::where('user_id', $user->id)->count(), 'Leave balances are initialised');

        $invitation->refresh();
        $this->assertTrue($invitation->isAccepted());
        $this->assertEquals($user->id, $invitation->accepted_user_id);
    }

    public function test_an_invitation_cannot_be_accepted_twice(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitation = $this->makeInvitation($tenant, $owner);

        app(InvitationService::class)->accept($invitation, ['name' => 'New Bie', 'password' => 'password123']);

        $this->expectException(InvitationException::class);

        app(InvitationService::class)->accept($invitation->fresh(), [
            'name' => 'Someone Else',
            'password' => 'password123',
        ]);
    }

    public function test_an_expired_invitation_cannot_be_accepted(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitation = $this->makeInvitation($tenant, $owner, expiresAt: now()->subDay()->toDateTimeString());

        $this->expectException(InvitationException::class);

        app(InvitationService::class)->accept($invitation, ['name' => 'New Bie', 'password' => 'password123']);
    }

    public function test_resending_renews_the_token_and_expiry(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitation = $this->makeInvitation($tenant, $owner, expiresAt: now()->subDay()->toDateTimeString());

        $oldToken = $invitation->token;

        app(InvitationService::class)->resend($invitation, $owner);

        $invitation->refresh();

        $this->assertNotEquals($oldToken, $invitation->token);
        $this->assertTrue($invitation->isPending(), 'The renewed invitation is usable again');
        Mail::assertSent(TeamInvitation::class);
    }

    public function test_revoking_removes_the_invitation(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $invitation = $this->makeInvitation($tenant, $owner);

        app(InvitationService::class)->revoke($invitation);

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_pending_invitations_exclude_accepted_ones(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->makeInvitation($tenant, $owner, 'pending@example.com');
        $this->makeInvitation($tenant, $owner, 'accepted@example.com', acceptedAt: now()->toDateTimeString());

        $pending = app(InvitationService::class)->pendingFor($tenant);

        $this->assertCount(1, $pending);
        $this->assertEquals('pending@example.com', $pending->first()->email);
    }

    public function test_team_page_invites_via_livewire(): void
    {
        Mail::fake();

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(TeamMembers::class)
            ->set('email', 'newbie@example.com')
            ->set('name', 'New Bie')
            ->set('role', 'Employee')
            ->call('invite')
            ->assertSet('error_message', null)
            ->assertSet('email', '')
            ->assertSee('Invitation sent');

        $this->assertDatabaseHas('invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'newbie@example.com',
            'role' => 'Employee',
        ]);
    }

    public function test_team_page_reports_a_rejected_invite(): void
    {
        Mail::fake();

        ['user' => $owner] = $this->provisionCompany();

        Livewire::actingAs($owner)
            ->test(TeamMembers::class)
            ->set('email', $owner->email)
            ->call('invite')
            ->assertSee('already belongs to an account');
    }

    public function test_accept_page_creates_the_account_and_signs_in(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $this->makeInvitation($tenant, $owner, 'newbie@example.com', 'Employee', 'plain-token-123');

        Livewire::test(AcceptInvitation::class, ['token' => 'plain-token-123'])
            ->assertSet('email', 'newbie@example.com')
            ->assertSet('tenantName', $owner->tenant->name)
            ->set('name', 'New Bie')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('accept')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'newbie@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('Employee'));
        $this->assertTrue(Invitation::first()->isAccepted());
    }

    public function test_accept_page_rejects_an_unknown_token(): void
    {
        Livewire::test(AcceptInvitation::class, ['token' => 'nope'])
            ->assertSet('email', '')
            ->assertSee('not valid');
    }

    public function test_accept_page_rejects_an_expired_invitation(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $this->makeInvitation($tenant, $owner, 'newbie@example.com', 'Employee', 'plain-token-123', expiresAt: now()->subDay()->toDateTimeString());

        Livewire::test(AcceptInvitation::class, ['token' => 'plain-token-123'])
            ->assertSee('expired');
    }

    public function test_accept_page_shows_a_friendly_error_when_the_email_is_already_registered(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $this->makeInvitation($tenant, $owner, $owner->email, 'Employee', 'plain-token-123');

        Livewire::test(AcceptInvitation::class, ['token' => 'plain-token-123'])
            ->assertSee('already belongs to an account');
    }

    public function test_invitation_link_is_reachable_by_guests(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $this->makeInvitation($tenant, $owner, 'newbie@example.com', 'Employee', 'plain-token-123');

        $this->get(route('invitation.accept', ['token' => 'plain-token-123']))
            ->assertOk()
            ->assertSee('newbie@example.com');
    }

    public function test_team_page_requires_a_super_admin(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner)->get(route('team.index'))->assertOk();

        $employee = User::factory()->create(['tenant_id' => $tenant->id]);
        $employee->assignRole('Employee');

        $this->actingAs($employee)->get(route('team.index'))->assertForbidden();
    }

    public function test_component_mount_blocks_non_super_admins(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        $employee = User::factory()->create(['tenant_id' => $tenant->id]);
        $employee->assignRole('Employee');

        Livewire::actingAs($employee)
            ->test(TeamMembers::class)
            ->assertForbidden();
    }
}
