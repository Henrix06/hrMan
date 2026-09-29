<?php

namespace App\Exceptions\License;

use RuntimeException;

class LicenseRedemptionException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('That licence code could not be found. Please check it and try again.');
    }

    public static function inactive(): self
    {
        return new self('That licence code has been disabled.');
    }

    public static function expired(): self
    {
        return new self('That licence code has expired.');
    }

    public static function exhausted(): self
    {
        return new self('That licence code has already been used the maximum number of times.');
    }

    public static function alreadyRedeemed(): self
    {
        return new self('Your workspace has already redeemed this licence code.');
    }

    public static function unknownPlan(string $plan): self
    {
        return new self("That licence code references an unknown plan ({$plan}).");
    }

    public static function noWorkspace(): self
    {
        return new self('Your account is not linked to a workspace, so a licence cannot be applied.');
    }
}
