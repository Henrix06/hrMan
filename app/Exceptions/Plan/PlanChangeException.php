<?php

namespace App\Exceptions\Plan;

use RuntimeException;

class PlanChangeException extends RuntimeException
{
    public static function samePlan(): self
    {
        return new self('Your workspace is already on that plan.');
    }

    public static function unknownPlan(string $plan): self
    {
        return new self("The plan '{$plan}' is not available.");
    }

    public static function contactSales(string $planName): self
    {
        return new self("{$planName} is tailored and priced per customer — contact us to enable it.");
    }

    public static function noWorkspace(): self
    {
        return new self('Your account is not linked to a workspace, so a plan cannot be changed.');
    }
}
