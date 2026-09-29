<?php

namespace App\Exceptions\Invitation;

use RuntimeException;

class InvitationException extends RuntimeException
{
    public static function alreadyMember(): self
    {
        return new self('That email already belongs to an account. They can simply sign in.');
    }

    public static function alreadyInvited(): self
    {
        return new self('An invitation is already pending for that email — resend or cancel it instead.');
    }

    public static function unknownRole(string $role): self
    {
        return new self("The role '{$role}' does not exist.");
    }

    public static function notFound(): self
    {
        return new self('This invitation link is not valid. Ask your administrator to send a new one.');
    }

    public static function expired(): self
    {
        return new self('This invitation has expired. Ask your administrator to send a new one.');
    }

    public static function alreadyAccepted(): self
    {
        return new self('This invitation has already been accepted. Please sign in instead.');
    }

    public static function noWorkspace(): self
    {
        return new self('Your account is not linked to a workspace, so you cannot invite teammates.');
    }
}
