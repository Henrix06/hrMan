<?php

namespace App\Contracts;

interface FeatureFlagServiceInterface
{
    public function isEnabled(string $flag, ?int $tenantId = null): bool;
    public function enable(string $flag, int $tenantId): void;
    public function disable(string $flag, int $tenantId): void;
    public function sync(array $flags, int $tenantId): void;
}
