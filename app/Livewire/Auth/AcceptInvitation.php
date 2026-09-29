<?php

namespace App\Livewire\Auth;

use App\Exceptions\Invitation\InvitationException;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.guest')]
class AcceptInvitation extends Component
{
    #[Locked]
    public string $token = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $error = null;

    public ?string $tenantName = null;

    public ?string $role = null;

    public function mount(string $token, InvitationService $invitations): void
    {
        $this->token = $token;

        $invitation = $this->resolve($invitations);

        if (! $invitation) {
            return;
        }

        $this->email = $invitation->email;
        $this->name = $invitation->name ?? '';
        $this->tenantName = $invitation->tenant?->name;
        $this->role = $invitation->role;
    }

    public function accept(InvitationService $invitations): void
    {
        if ($this->error !== null) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $invitation = $this->resolve($invitations);

        if (! $invitation) {
            return;
        }

        try {
            $user = $invitations->accept($invitation, [
                'name' => $this->name,
                'password' => $this->password,
            ]);
        } catch (InvitationException $e) {
            $this->error = $e->getMessage();

            return;
        }

        Auth::login($user);

        $this->redirectRoute('dashboard');
    }

    /**
     * Look up the invitation and surface a friendly reason when it is unusable.
     */
    private function resolve(InvitationService $invitations): ?Invitation
    {
        $invitation = $invitations->findByToken($this->token);

        if (! $invitation) {
            $this->error = InvitationException::notFound()->getMessage();

            return null;
        }

        if ($invitation->isAccepted()) {
            $this->error = InvitationException::alreadyAccepted()->getMessage();

            return null;
        }

        if ($invitation->isExpired()) {
            $this->error = InvitationException::expired()->getMessage();

            return null;
        }

        if (User::whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->exists()) {
            $this->error = InvitationException::alreadyMember()->getMessage();

            return null;
        }

        return $invitation;
    }

    public function render()
    {
        return view('livewire.auth.accept-invitation');
    }
}
