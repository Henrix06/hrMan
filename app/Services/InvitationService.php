<?php

namespace App\Services;

use App\Actions\Employees\InitializeLeaveBalances;
use App\Exceptions\Invitation\InvitationException;
use App\Mail\TeamInvitation;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Employees\EmployeeIdGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InvitationService
{
    public function __construct(private readonly InitializeLeaveBalances $leaveBalances) {}

    /**
     * Invite an email address to join a workspace with a given role.
     *
     * @throws InvitationException
     */
    public function invite(Tenant $tenant, array $data, ?User $inviter = null): Invitation
    {
        $email = strtolower(trim($data['email']));
        $role = $data['role'];

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw InvitationException::alreadyMember();
        }

        if (! Role::where('name', $role)->exists()) {
            throw InvitationException::unknownRole($role);
        }

        $alreadyPending = Invitation::where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->exists();

        if ($alreadyPending) {
            throw InvitationException::alreadyInvited();
        }

        [$invitation, $plainToken] = $this->createInvitation($tenant, [
            'email' => $email,
            'name' => $data['name'] ?? null,
            'role' => $role,
            'invited_by' => $inviter?->id,
        ]);

        $this->send($invitation, $plainToken, $inviter);

        if ($inviter !== null) {
            AuditLog::record('create', "Invited {$email} as {$role}", $invitation);
        }

        return $invitation;
    }

    /**
     * Accept an invitation and create the teammate's account.
     *
     * @throws InvitationException
     */
    public function accept(Invitation $invitation, array $data): User
    {
        if ($invitation->isAccepted()) {
            throw InvitationException::alreadyAccepted();
        }

        if ($invitation->isExpired()) {
            throw InvitationException::expired();
        }

        if (User::whereRaw('LOWER(email) = ?', [$invitation->email])->exists()) {
            throw InvitationException::alreadyMember();
        }

        if (! Role::where('name', $invitation->role)->exists()) {
            throw InvitationException::unknownRole($invitation->role);
        }

        return DB::transaction(function () use ($invitation, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
                'tenant_id' => $invitation->tenant_id,
                'employee_id' => app(EmployeeIdGenerator::class)->next(),
                'status' => 'active',
                'hire_date' => now()->toDateString(),
                'position' => $invitation->role,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($invitation->role);

            $this->leaveBalances->execute($user);

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ])->save();

            return $user;
        });
    }

    /**
     * Regenerate the token and send a fresh invitation email.
     *
     * @throws InvitationException
     */
    public function resend(Invitation $invitation, ?User $inviter = null): Invitation
    {
        if ($invitation->isAccepted()) {
            throw InvitationException::alreadyAccepted();
        }

        $plainToken = $this->renewToken($invitation);

        $this->send($invitation, $plainToken, $inviter);

        return $invitation;
    }

    public function revoke(Invitation $invitation): void
    {
        $invitation->delete();
    }

    /**
     * Resolve an invitation from the raw token carried in the emailed URL.
     */
    public function findByToken(string $plainToken): ?Invitation
    {
        if ($plainToken === '') {
            return null;
        }

        return Invitation::where('token', $this->hashToken($plainToken))->first();
    }

    /**
     * Pending invitations for a workspace, newest first.
     */
    public function pendingFor(Tenant $tenant): Collection
    {
        return Invitation::where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array{0: Invitation, 1: string}
     */
    private function createInvitation(Tenant $tenant, array $attributes): array
    {
        $plainToken = Str::random(48);

        $invitation = Invitation::create($attributes + [
            'tenant_id' => $tenant->id,
            'token' => $this->hashToken($plainToken),
            'expires_at' => now()->addDays((int) config('team.invitation_expiry_days', 7)),
        ]);

        return [$invitation, $plainToken];
    }

    private function renewToken(Invitation $invitation): string
    {
        $plainToken = Str::random(48);

        $invitation->forceFill([
            'token' => $this->hashToken($plainToken),
            'expires_at' => now()->addDays((int) config('team.invitation_expiry_days', 7)),
        ])->save();

        return $plainToken;
    }

    private function send(Invitation $invitation, string $plainToken, ?User $inviter): void
    {
        $acceptUrl = route('invitation.accept', ['token' => $plainToken]);

        try {
            Mail::to($invitation->email)->send(new TeamInvitation(
                $invitation,
                $acceptUrl,
                $invitation->tenant?->name ?? config('app.name'),
                $inviter?->name ?? 'A workspace administrator',
            ));
        } catch (\Throwable $e) {
            // The invitation row still exists, so an admin can resend it.
            report($e);
        }
    }

    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
