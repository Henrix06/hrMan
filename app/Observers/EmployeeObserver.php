<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class EmployeeObserver
{
    public function created(User $user): void
    {
        $this->clearCache();
    }

    public function updated(User $user): void
    {
        $this->clearCache();
    }

    public function deleted(User $user): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('cache_svc:employees:active');
    }
}
