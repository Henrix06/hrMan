<?php

namespace App\Observers;

use App\Models\Department;
use Illuminate\Support\Facades\Cache;

class DepartmentObserver
{
    public function created(Department $department): void
    {
        $this->clearCache();
    }

    public function updated(Department $department): void
    {
        $this->clearCache();
    }

    public function deleted(Department $department): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('cache_svc:departments:all');
    }
}
