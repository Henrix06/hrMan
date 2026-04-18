<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface ModuleRegistryInterface
{
    public function isActive(string $moduleKey, ?int $tenantId = null): bool;
    public function enable(string $moduleKey, int $tenantId): void;
    public function disable(string $moduleKey, int $tenantId): void;
    public function allForTenant(int $tenantId): Collection;
}
