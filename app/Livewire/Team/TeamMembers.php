<?php

namespace App\Livewire\Team;

use App\Exceptions\Invitation\InvitationException;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class TeamMembers extends Component
{
    public string $email = '';

    public string $name = '';

    public string $role = 'Employee';

    public ?string $status_message = null;

    public ?string $error_message = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('Super Admin'), 403);
    }

    public function invite(InvitationService $invitations): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $this->status_message = null;
        $this->error_message = null;

        $user = Auth::user();
        $tenant = $user?->tenant;

        if (! $tenant instanceof Tenant) {
            $this->error_message = InvitationException::noWorkspace()->getMessage();

            return;
        }

        try {
            $invitation = $invitations->invite($tenant, [
                'email' => $this->email,
                'name' => $this->name !== '' ? $this->name : null,
                'role' => $this->role,
            ], $user);
        } catch (InvitationException $e) {
            $this->error_message = $e->getMessage();

            return;
        }

        $this->email = '';
        $this->name = '';

        $this->status_message = "Invitation sent to {$invitation->email} as {$invitation->role}.";

        $this->dispatch('invitation-sent');
    }

    public function resend(string $invitationId, InvitationService $invitations): void
    {
        $this->status_message = null;
        $this->error_message = null;

        $invitation = $this->findInvitation($invitationId);

        if (! $invitation) {
            $this->error_message = 'That invitation is no longer available.';

            return;
        }

        try {
            $invitations->resend($invitation, Auth::user());
        } catch (InvitationException $e) {
            $this->error_message = $e->getMessage();

            return;
        }

        $this->status_message = "Invitation resent to {$invitation->email}.";
    }

    public function cancelInvitation(string $invitationId, InvitationService $invitations): void
    {
        $this->status_message = null;
        $this->error_message = null;

        $invitation = $this->findInvitation($invitationId);

        if (! $invitation) {
            $this->error_message = 'That invitation is no longer available.';

            return;
        }

        $email = $invitation->email;

        $invitations->revoke($invitation);

        $this->status_message = "Invitation for {$email} cancelled.";
    }

    private function findInvitation(string $invitationId): ?Invitation
    {
        $tenant = Auth::user()?->tenant;

        if (! $tenant instanceof Tenant) {
            return null;
        }

        return Invitation::where('tenant_id', $tenant->id)->find($invitationId);
    }

    public function getRolesProperty()
    {
        return Role::orderBy('name')->pluck('name');
    }

    public function render()
    {
        $tenant = Auth::user()?->tenant;

        return view('livewire.team.team-members', [
            'tenant' => $tenant,
            'members' => $tenant
                ? User::where('tenant_id', $tenant->id)->with('roles')->orderBy('name')->get()
                : collect(),
            'pending' => $tenant
                ? app(InvitationService::class)->pendingFor($tenant)
                : collect(),
        ]);
    }
}
