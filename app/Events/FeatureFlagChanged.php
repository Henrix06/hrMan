<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FeatureFlagChanged
{
    use Dispatchable;

    public function __construct(
        public readonly string $flagKey,
        public readonly int $tenantId,
        public readonly bool $isEnabled,
    ) {}
}
