<?php

namespace App\Providers;

use App\Contracts\FeatureFlagServiceInterface;
use App\Contracts\ModuleRegistryInterface;
use App\Contracts\RuleServiceInterface;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Policies\SalesOrderPolicy;
use App\Policies\TaskPolicy;
use App\Services\FeatureFlagService;
use App\Services\ModuleRegistry;
use App\Services\RuleEvaluator;
use App\Services\RuleService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RuleServiceInterface::class, function ($app) {
            return new RuleService($app->make(RuleEvaluator::class));
        });

        $this->app->bind(ModuleRegistryInterface::class, ModuleRegistry::class);

        $this->app->bind(FeatureFlagServiceInterface::class, function ($app) {
            return new FeatureFlagService($app->make(RuleServiceInterface::class));
        });
    }

    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);

        // Permission gate for module management
        Gate::define('manage-modules', function ($user) {
            return $user->hasPermissionTo('manage-modules');
        });
    }
}
