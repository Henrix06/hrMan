# Implementation Plan: Module Loader System, Feature Flags & Rules Engine

## Overview

Implement the three-layer access control stack in PHP/Laravel: a Module Registry backed by `tenant_modules`, a Feature Flag Service backed by `feature_flags`, and a Rules Engine backed by `module_rules`. All services use Laravel's cache layer and are wired into HTTP middleware that protects existing route groups.

## Tasks

- [x] 1. Install nwidart/laravel-modules and run initial setup
  - Run `composer require nwidart/laravel-modules` and publish the package config
  - Add `"Modules\\": "Modules/"` to `composer.json` autoload psr-4 and run `composer dump-autoload`
  - Publish the modules config: `php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"`
  - _Requirements: 1.1_

- [x] 2. Create database migrations for the three new tables
  - [x] 2.1 Create migration for `tenant_modules` table
    - Create `database/migrations/YYYY_MM_DD_000001_create_tenant_modules_table.php`
    - Define columns: `id`, `tenant_id` (unsignedBigInteger, default 1, indexed), `module_key` (string), `is_enabled` (boolean, default true), `metadata` (json, nullable), `timestamps`
    - Add unique composite index on `['tenant_id', 'module_key']`
    - _Requirements: 1.1, 1.5_

  - [x] 2.2 Create migration for `feature_flags` table
    - Create `database/migrations/YYYY_MM_DD_000002_create_feature_flags_table.php`
    - Define columns: `id`, `tenant_id` (unsignedBigInteger, default 1, indexed), `flag_key` (string), `is_enabled` (boolean, default true), `description` (text, nullable), `timestamps`
    - Add unique composite index on `['tenant_id', 'flag_key']`
    - _Requirements: 3.4, 3.5_

  - [x] 2.3 Create migration for `module_rules` table
    - Create `database/migrations/YYYY_MM_DD_000003_create_module_rules_table.php`
    - Define columns: `id`, `tenant_id` (unsignedBigInteger, default 1, indexed), `subject` (string), `rule_key` (string, unique), `definition` (json), `is_active` (boolean, default true), `priority` (integer, default 0), `timestamps`
    - Add composite index on `['tenant_id', 'subject']`
    - _Requirements: 5.3, 5.5, 14.4_

- [x] 3. Create Eloquent models for the three tables
  - [x] 3.1 Create `app/Models/TenantModule.php`
    - Set `$fillable`: `tenant_id`, `module_key`, `is_enabled`, `metadata`
    - Cast `is_enabled` to boolean, `metadata` to array
    - _Requirements: 1.1, 2.1, 2.2_

  - [x] 3.2 Create `app/Models/FeatureFlag.php`
    - Set `$fillable`: `tenant_id`, `flag_key`, `is_enabled`, `description`
    - Cast `is_enabled` to boolean
    - _Requirements: 3.1, 4.1, 4.2_

  - [x] 3.3 Create `app/Models/ModuleRule.php`
    - Set `$fillable`: `tenant_id`, `subject`, `rule_key`, `definition`, `is_active`, `priority`
    - Cast `is_active` to boolean, `definition` to array
    - _Requirements: 5.1, 14.1, 14.2_

- [x] 4. Create custom exception classes
  - [x] 4.1 Create `app/Exceptions/InvalidModuleKeyException.php`
    - Extend `\RuntimeException`
    - Accept the invalid key in the constructor and include it in the message: `"Invalid module key: {key}"`
    - _Requirements: 1.4_

  - [x] 4.2 Create `app/Exceptions/InvalidRuleDefinitionException.php`
    - Extend `\RuntimeException`
    - Accept a descriptive message and optional context (subject, rule_key) in the constructor
    - _Requirements: 5.4, 7.11_

- [x] 5. Define service contracts (interfaces)
  - [x] 5.1 Create `app/Contracts/ModuleRegistryInterface.php`
    - Declare methods: `isActive(string $moduleKey, ?int $tenantId = null): bool`, `enable(string $moduleKey, int $tenantId): void`, `disable(string $moduleKey, int $tenantId): void`, `allForTenant(int $tenantId): \Illuminate\Support\Collection`
    - _Requirements: 1.1, 2.1, 2.2_

  - [x] 5.2 Create `app/Contracts/FeatureFlagServiceInterface.php`
    - Declare methods: `isEnabled(string $flag, ?int $tenantId = null): bool`, `enable(string $flag, int $tenantId): void`, `disable(string $flag, int $tenantId): void`, `sync(array $flags, int $tenantId): void`
    - _Requirements: 3.1, 4.1, 4.2, 4.5_

  - [x] 5.3 Create `app/Contracts/RuleServiceInterface.php`
    - Declare methods: `evaluate(string $subject, array $context = []): bool`, `upsert(string $subject, string $ruleKey, array $ruleDefinition): void`, `remove(string $subject, string $ruleKey): void`
    - _Requirements: 5.1, 14.1, 14.2_

- [x] 6. Implement `EvaluationContext` class
  - Create `app/Services/EvaluationContext.php`
  - Constructor accepts `?int $tenantId` and resolves all fields from server-side sources only
  - Populate `plan` from `config/pricing.php` (default `'free'` when no tenant record)
  - Populate `employee_count` by counting active `User` records for the tenant
  - Populate `user_role` from `Auth::user()->getRoleNames()->first()` via Spatie
  - Populate `module_key` as a settable property (set by callers before passing to RuleService)
  - Populate `date` as `now()->toDateString()` (ISO 8601)
  - Populate `day_of_week` as `(int) now()->dayOfWeek`
  - Expose `toArray(): array` returning all six fields as a flat associative array
  - _Requirements: 8.1–8.7_

- [x] 7. Implement `RuleEvaluator` class
  - Create `app/Services/RuleEvaluator.php`
  - [x] 7.1 Implement `applyOperator(string $operator, mixed $fieldValue, mixed $ruleValue): bool`
    - Handle all 10 operators: `equals`, `not_equals`, `in`, `not_in`, `gte`, `lte`, `gt`, `lt`, `contains`, `not_contains`
    - Throw `InvalidRuleDefinitionException` for any unrecognised operator string
    - _Requirements: 7.1–7.11_

  - [x] 7.2 Implement `evaluateDefinition(array $definition, array $context): bool`
    - Parse `combinator` (default `"and"`) and `conditions` array from `$definition`
    - Throw `InvalidRuleDefinitionException` when `conditions` key is missing
    - Apply `and` combinator: return `true` iff ALL conditions pass
    - Apply `or` combinator: return `true` iff ANY condition passes
    - Apply `not` combinator: return logical negation of the `and` result
    - _Requirements: 6.1–6.4_

  - [ ]* 7.3 Write unit tests for `RuleEvaluator::applyOperator()`
    - Use PHPUnit data providers to cover all 10 operators with matching and non-matching values
    - Include edge cases: null field value, empty string, zero, empty array
    - Assert `InvalidRuleDefinitionException` is thrown for unknown operator
    - **Property 6: Operator determinism** — same inputs always produce same output
    - **Property 7: Unknown operator throws**
    - **Validates: Requirements 7.1–7.11**

  - [ ]* 7.4 Write unit tests for `RuleEvaluator::evaluateDefinition()` combinators
    - Test `and` combinator: all-true → true, one-false → false
    - Test `or` combinator: all-false → false, one-true → true
    - Test `not` combinator: negation of `and` result
    - Test missing `combinator` defaults to `and`
    - **Property 8: AND combinator semantics**
    - **Property 9: OR combinator semantics**
    - **Property 10: NOT combinator semantics**
    - **Validates: Requirements 6.1–6.4**

- [x] 8. Implement `RuleService`
  - Create `app/Services/RuleService.php` implementing `RuleServiceInterface`
  - [x] 8.1 Implement `evaluate(string $subject, array $context = []): bool`
    - Build cache key `module_rules:{tenantId}:{subject}`, TTL 120 s; wrap cache calls in try/catch for resilience (fall through to DB on cache failure)
    - Load active rules from `module_rules` ordered by `priority` DESC
    - Return `true` immediately when no active rules exist
    - Iterate rules; for each, decode `definition` and call `RuleEvaluator::evaluateDefinition()`
    - On `InvalidRuleDefinitionException`: log error, treat rule as passing (`true`), continue
    - Return `false` as soon as any rule fails (AND semantics across rules)
    - _Requirements: 5.1–5.5, 12.3_

  - [x] 8.2 Implement `upsert(string $subject, string $ruleKey, array $ruleDefinition): void`
    - Gate with `Gate::authorize('permission:manage-modules')` check
    - Upsert into `module_rules` matching `subject` + `rule_key`
    - Invalidate cache key `module_rules:{tenantId}:{subject}` after write
    - _Requirements: 13.2, 14.1, 14.3_

  - [x] 8.3 Implement `remove(string $subject, string $ruleKey): void`
    - Gate with `manage-modules` permission check
    - Delete matching row from `module_rules`
    - Invalidate cache key `module_rules:{tenantId}:{subject}` after delete
    - _Requirements: 13.2, 14.2, 14.3_

  - [ ]* 8.4 Write unit tests for `RuleService::evaluate()`
    - Test permissive default: no rules → returns `true`
    - Test multi-rule AND: one failing rule → returns `false`
    - Test malformed JSON rule: logs error, treats as passing, continues
    - **Property 3: Rule permissiveness**
    - **Property 11: Multi-rule AND semantics**
    - **Validates: Requirements 5.1, 5.2, 5.4**

- [x] 9. Checkpoint — run `php artisan test` and ensure all existing tests still pass

- [x] 10. Implement `ModuleRegistry`
  - Create `app/Services/ModuleRegistry.php` implementing `ModuleRegistryInterface`
  - [x] 10.1 Implement `isActive(string $moduleKey, ?int $tenantId = null): bool`
    - Validate `$moduleKey` against `config('pricing.modules')` keys; throw `InvalidModuleKeyException` if not found
    - Resolve `$tenantId` to `1` when null (single-tenant default)
    - Build cache key `module_active:{tenantId}:{moduleKey}`, TTL 60 s; wrap cache in try/catch for resilience
    - Query `tenant_modules` for matching row; if found return `is_enabled`
    - If no row, fall back to `config("pricing.plans.{plan}.modules")` and check if `$moduleKey` is in the array
    - _Requirements: 1.1–1.5, 12.1_

  - [x] 10.2 Implement `enable()` and `disable()` with cache invalidation
    - Use `TenantModule::updateOrCreate(['tenant_id' => $tenantId, 'module_key' => $moduleKey], ['is_enabled' => true/false])`
    - Call `Cache::forget("module_active:{$tenantId}:{$moduleKey}")` before returning
    - _Requirements: 2.1–2.3_

  - [x] 10.3 Implement `allForTenant(int $tenantId): Collection`
    - Return all `TenantModule` rows for the tenant merged with plan defaults for any missing module keys
    - _Requirements: 1.1_

  - [ ]* 10.4 Write unit tests for `ModuleRegistry`
    - Test plan fallback: module in plan config, no DB row → `isActive()` returns `true`
    - Test explicit DB row overrides plan default
    - Test `InvalidModuleKeyException` thrown for unknown key
    - Test cache invalidation: `disable()` then `isActive()` returns `false` without stale cache
    - **Property 4: Pricing plan fallback consistency**
    - **Property 5: Cache coherence after toggle**
    - **Validates: Requirements 1.2, 1.3, 1.4, 2.3**

- [x] 11. Implement `FeatureFlagService`
  - Create `app/Services/FeatureFlagService.php` implementing `FeatureFlagServiceInterface`
  - [x] 11.1 Implement `isEnabled(string $flag, ?int $tenantId = null): bool`
    - Resolve `$tenantId` to `1` when null
    - Build cache key `feature_flag:{tenantId}:{flagKey}`, TTL 30 s; wrap cache in try/catch for resilience
    - Query `feature_flags` for matching row; if no row return `true` (opt-out model)
    - If row exists and `is_enabled = false`, return `false` immediately
    - If row exists and `is_enabled = true`, delegate to `RuleService::evaluate($flag, $context)` and return result
    - _Requirements: 3.1–3.4, 12.2_

  - [x] 11.2 Implement `enable()` and `disable()` with cache invalidation and event dispatch
    - Use `FeatureFlag::updateOrCreate(...)` to upsert the row
    - Call `Cache::forget("feature_flag:{$tenantId}:{$flagKey}")` before returning
    - Dispatch `FeatureFlagChanged` event with `flagKey`, `tenantId`, and new `isEnabled` state
    - _Requirements: 4.1–4.4_

  - [x] 11.3 Create `app/Events/FeatureFlagChanged.php`
    - Public constructor properties: `string $flagKey`, `int $tenantId`, `bool $isEnabled`
    - _Requirements: 4.4_

  - [x] 11.4 Implement `sync(array $flags, int $tenantId): void`
    - Iterate `$flags` array and call `enable()` or `disable()` for each key/value pair
    - _Requirements: 4.5_

  - [ ]* 11.5 Write unit tests for `FeatureFlagService`
    - Test opt-out default: no DB row → `isEnabled()` returns `true`
    - Test `is_enabled = false` in DB → returns `false` without calling RuleService
    - Test `is_enabled = true` in DB → delegates to RuleService and returns its result
    - Test `FeatureFlagChanged` event is dispatched on `enable()` and `disable()`
    - **Property 2: Opt-out flag semantics**
    - **Property 5: Cache coherence after toggle**
    - **Validates: Requirements 3.1, 3.2, 3.3, 4.3, 4.4**

- [x] 12. Implement HTTP middleware
  - [x] 12.1 Create `app/Http/Middleware/ModuleActiveMiddleware.php`
    - Constructor-inject `ModuleRegistryInterface`
    - In `handle()`: extract module key from middleware parameter (e.g. `module:payroll`)
    - Call `$registry->isActive($moduleKey)`; if `false`, return `response()->view('errors.module-disabled', [], 403)`
    - Otherwise call `$next($request)`
    - _Requirements: 9.1–9.3_

  - [x] 12.2 Create `app/Http/Middleware/FeatureFlagMiddleware.php`
    - Constructor-inject `FeatureFlagServiceInterface`
    - In `handle()`: extract flag key from middleware parameter (e.g. `feature:payroll`)
    - Call `$service->isEnabled($flagKey)`; if `false`, return `abort(403)`
    - Otherwise call `$next($request)`
    - _Requirements: 10.1–10.3_

- [x] 13. Create `errors.module-disabled` Blade view
  - Create `resources/views/errors/module-disabled.blade.php`
  - Extend the app layout (`<x-app-layout>`)
  - Display a clear "Module Not Available" heading and a message explaining the module is not included in the current plan
  - Include a link to `route('pricing')` prompting the user to upgrade
  - _Requirements: 9.1_

- [x] 14. Create `ModuleSyncCommand` Artisan command
  - Create `app/Console/Commands/ModuleSyncCommand.php`
  - Signature: `modules:sync {--tenant=1 : Tenant ID to sync} {--plan= : Override plan key}`
  - Logic: resolve tenant ID and plan from options; iterate `config('pricing.plans.{plan}.modules')`; upsert each module key into `tenant_modules` with `is_enabled = true` using `TenantModule::updateOrCreate()`
  - Modules NOT in the plan should be upserted with `is_enabled = false` if a row already exists
  - Print a summary line per module processed
  - _Requirements: 2.4, 12.1 (idempotence — Property 12)_

- [x] 15. Register services and middleware in the application bootstrap
  - [x] 15.1 Update `app/Providers/AppServiceProvider.php`
    - In `register()`: bind `ModuleRegistryInterface` → `ModuleRegistry::class`, `FeatureFlagServiceInterface` → `FeatureFlagService::class`, `RuleServiceInterface` → `RuleService::class` using `$this->app->bind()`
    - _Requirements: 1.1, 3.1, 5.1_

  - [x] 15.2 Update `bootstrap/app.php` to register middleware aliases
    - Inside the `->withMiddleware()` callback, add to the existing `$middleware->alias([...])` array:
      - `'module' => \App\Http\Middleware\ModuleActiveMiddleware::class`
      - `'feature' => \App\Http\Middleware\FeatureFlagMiddleware::class`
    - _Requirements: 9.3, 10.3_

- [x] 16. Apply module and feature middleware to existing route groups in `routes/web.php`
  - Add `->middleware(['module:leave_management'])` to the `leave` prefix group
  - Add `->middleware(['module:payroll'])` to the `payroll` and `payslips` prefix groups
  - Add `->middleware(['module:accounting'])` to the `accounts` prefix group
  - Add `->middleware(['module:asset_management'])` to the `assets` prefix group
  - Add `->middleware(['module:task_management'])` to the `tasks` prefix group
  - Add `->middleware(['module:announcements'])` to the `announcements` prefix group
  - Add `->middleware(['module:audit_logs', 'permission:manage-modules'])` to the `admin` prefix group
  - Ensure `ModuleActiveMiddleware` is listed before any `feature:` middleware on the same group
  - _Requirements: 9.1–9.3, 10.3, 11.1, 11.2_

- [x] 17. Checkpoint — run `php artisan test` and ensure all tests pass; manually verify `php artisan modules:sync` runs without errors

- [ ] 18. Write integration tests for middleware 403 responses
  - [ ]* 18.1 Write feature test for `ModuleActiveMiddleware` returning 403
    - Create `tests/Feature/ModuleActiveMiddlewareTest.php`
    - Test: disable a module via `ModuleRegistry::disable()`, make authenticated GET request to a protected route, assert HTTP 403 response
    - Test: enable a module, make authenticated GET request, assert HTTP 200 response
    - **Validates: Requirements 9.1, 9.2**

  - [ ]* 18.2 Write feature test for `FeatureFlagMiddleware` returning 403
    - Create `tests/Feature/FeatureFlagMiddlewareTest.php`
    - Test: disable a feature flag via `FeatureFlagService::disable()`, make authenticated GET request to a feature-protected route, assert HTTP 403 response
    - Test: enable a feature flag, make authenticated GET request, assert HTTP 200 response
    - **Validates: Requirements 10.1, 10.2**

  - [ ]* 18.3 Write integration test for RuleService plan gate
    - Test: `RuleService::evaluate('payroll', ['plan' => 'free'])` with a `plan in [professional, enterprise]` rule → assert `false`
    - Test: same rule with `['plan' => 'professional']` → assert `true`
    - **Property 11: Multi-rule AND semantics**
    - **Validates: Requirements 5.2**

- [x] 19. Final checkpoint — run `php artisan test` and ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical boundaries
- The `tenant_id` defaults to `1` throughout for single-tenant operation; multi-tenancy requires only resolving this value dynamically
- Property tests validate universal correctness properties defined in the design document
- Unit tests validate specific examples and edge cases
- `nwidart/laravel-modules` is used for module manifest and autoloading; the Module Registry reads its manifest to confirm a module is installed before checking the DB
