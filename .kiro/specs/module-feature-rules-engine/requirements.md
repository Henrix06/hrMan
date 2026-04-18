# Requirements Document

## Introduction

This document defines the requirements for the Module Loader System, Feature Flags layer, and Rules Engine for the uMsizi HR Management Portal. The three subsystems form a layered access-control stack: the Module Loader determines whether a module is installed and enabled per tenant; the Feature Flag layer gates individual features within those modules; and the Rules Engine evaluates JSON-encoded business rules against a runtime context (pricing plan, employee count, user role, date) to determine access at runtime.

The system is designed for single-tenant operation today, with `tenant_id` columns on all tables to support multi-tenancy without schema changes in the future. The existing `config/pricing.php` plan definitions serve as the canonical source of truth for which modules belong to which plan.

---

## Glossary

- **ModuleRegistry**: The service responsible for querying and toggling per-tenant module enabled state.
- **FeatureFlagService**: The service responsible for querying and toggling per-tenant feature flag state.
- **RuleService**: The service responsible for loading and evaluating JSON rule definitions from the database.
- **RuleEvaluator**: The internal component of RuleService that applies combinators and operators to an EvaluationContext.
- **EvaluationContext**: The runtime data map (plan, employee_count, user_role, module_key, date, day_of_week) built from server-side sources and passed to the RuleEvaluator.
- **ModuleActiveMiddleware**: The HTTP middleware that checks module enabled state and returns 403 when a module is disabled.
- **FeatureFlagMiddleware**: The HTTP middleware that checks feature flag enabled state and returns 403 when a feature is disabled.
- **Tenant**: A logical organisation unit identified by `tenant_id`; currently always ID 1 in single-tenant mode.
- **Module**: A discrete functional area of the application (e.g. `payroll`, `leave_management`) registered in `config/pricing.php`.
- **Feature Flag**: A fine-grained on/off switch for a specific capability within a module, identified by dot-notation key (e.g. `payroll.bulk_run`).
- **Rule**: A JSON-encoded definition stored in `module_rules` containing a combinator and a list of conditions evaluated against the EvaluationContext.
- **Combinator**: The logical operator (`and`, `or`, `not`) that combines condition results within a single rule definition.
- **Operator**: One of the ten comparison functions (`equals`, `not_equals`, `in`, `not_in`, `gte`, `lte`, `gt`, `lt`, `contains`, `not_contains`) applied to a field value and a rule value.
- **InvalidModuleKeyException**: Exception thrown when a module key is not present in `config('pricing.modules')`.
- **InvalidRuleDefinitionException**: Exception thrown when a rule's JSON is malformed or missing required keys.

---

## Requirements

### Requirement 1: Module Registry — State Query

**User Story:** As a tenant administrator, I want the system to accurately report whether a module is active for my tenant, so that access control decisions are consistent and reliable.

#### Acceptance Criteria

1. THE ModuleRegistry SHALL return `true` for a module only when the module is both present in the nWidart module manifest AND enabled for the tenant.
2. WHEN no `tenant_modules` row exists for a given tenant and module key, THE ModuleRegistry SHALL fall back to the tenant's pricing plan definition in `config/pricing.php` to determine the enabled state.
3. WHEN a module key is present in `config('pricing.plans.{plan}.modules')` and the tenant is on that plan and no explicit `tenant_modules` row exists, THE ModuleRegistry SHALL return `true` for that module.
4. WHEN a module key is not present in `config('pricing.modules')`, THE ModuleRegistry SHALL throw `InvalidModuleKeyException` containing the invalid key.
5. THE ModuleRegistry SHALL cache the result of each module state lookup under the key `module_active:{tenantId}:{moduleKey}` with a TTL of 60 seconds.

---

### Requirement 2: Module Registry — State Mutation

**User Story:** As a tenant administrator, I want to enable or disable modules for my tenant, so that I can control which functionality is available to my users.

#### Acceptance Criteria

1. WHEN `ModuleRegistry::enable()` is called for a module and tenant, THE ModuleRegistry SHALL set `is_enabled = true` in the `tenant_modules` table, creating the row if it does not exist.
2. WHEN `ModuleRegistry::disable()` is called for a module and tenant, THE ModuleRegistry SHALL set `is_enabled = false` in the `tenant_modules` table, creating the row if it does not exist.
3. WHEN `enable()` or `disable()` is called, THE ModuleRegistry SHALL invalidate the cache key `module_active:{tenantId}:{moduleKey}` before the method returns, so the next call to `isActive()` reflects the updated state.
4. THE `modules:sync` Artisan command SHALL seed the `tenant_modules` table from `config/pricing.php` plan definitions using an upsert operation, making the command safe to run multiple times without duplicating rows.

---

### Requirement 3: Feature Flag Service — State Query

**User Story:** As a developer, I want to check whether a specific feature is enabled for a tenant, so that I can conditionally render UI elements and guard business logic.

#### Acceptance Criteria

1. WHEN no `feature_flags` row exists for a given tenant and flag key, THE FeatureFlagService SHALL return `true` (opt-out model — unknown flags are considered enabled).
2. WHEN a `feature_flags` row exists with `is_enabled = false`, THE FeatureFlagService SHALL return `false` regardless of any associated rules.
3. WHEN a `feature_flags` row exists with `is_enabled = true`, THE FeatureFlagService SHALL delegate to `RuleService::evaluate()` with the flag key and a built EvaluationContext, and return the result of that evaluation.
4. THE FeatureFlagService SHALL cache the result of each flag lookup under the key `feature_flag:{tenantId}:{flagKey}` with a TTL of 30 seconds.
5. THE `flag_key` field SHALL follow dot-notation format `{module}.{feature}` (e.g. `payroll.bulk_run`, `leave_management.approval_workflow`).

---

### Requirement 4: Feature Flag Service — State Mutation

**User Story:** As a tenant administrator, I want to enable or disable individual feature flags, so that I can fine-tune which capabilities are available within an active module.

#### Acceptance Criteria

1. WHEN `FeatureFlagService::enable()` is called for a flag and tenant, THE FeatureFlagService SHALL set `is_enabled = true` in the `feature_flags` table, creating the row if it does not exist.
2. WHEN `FeatureFlagService::disable()` is called for a flag and tenant, THE FeatureFlagService SHALL set `is_enabled = false` in the `feature_flags` table, creating the row if it does not exist.
3. WHEN `enable()` or `disable()` is called, THE FeatureFlagService SHALL invalidate the cache key `feature_flag:{tenantId}:{flagKey}` before the method returns.
4. WHEN `enable()` or `disable()` is called, THE FeatureFlagService SHALL fire a `FeatureFlagChanged` event containing the flag key, tenant ID, and new enabled state.
5. THE `FeatureFlagService::sync()` method SHALL accept an array of `['flag_key' => bool]` pairs and apply each as an upsert to the `feature_flags` table for the given tenant.

---

### Requirement 5: Rule Service — Rule Evaluation

**User Story:** As a system architect, I want business rules stored in the database to be evaluated against a runtime context, so that module and feature availability can be controlled dynamically without code deployments.

#### Acceptance Criteria

1. WHEN no active rules exist in `module_rules` for a given subject, THE RuleService SHALL return `true` (permissive default).
2. WHEN one or more active rules exist for a subject, THE RuleService SHALL evaluate all active rules and return `false` if ANY rule evaluates to `false` (AND semantics across multiple rules).
3. THE RuleService SHALL load rules ordered by `priority` descending, evaluating higher-priority rules first.
4. WHEN a rule's `definition` JSON is malformed or missing required keys (`combinator`, `conditions`), THE RuleService SHALL throw `InvalidRuleDefinitionException`, log the error, and treat that rule as passing (`true`) to avoid locking out tenants due to bad data.
5. THE RuleService SHALL cache loaded rules per subject under the key `module_rules:{tenantId}:{subject}` with a TTL of 120 seconds.

---

### Requirement 6: Rule Evaluator — Combinators

**User Story:** As a system architect, I want rule definitions to support logical combinators, so that complex access conditions can be expressed as composable rule structures.

#### Acceptance Criteria

1. WHEN a rule definition specifies `combinator = "and"`, THE RuleEvaluator SHALL return `true` if and only if ALL conditions in the definition evaluate to `true`.
2. WHEN a rule definition specifies `combinator = "or"`, THE RuleEvaluator SHALL return `true` if and only if AT LEAST ONE condition in the definition evaluates to `true`.
3. WHEN a rule definition specifies `combinator = "not"`, THE RuleEvaluator SHALL return the logical negation of the result that would be produced by `combinator = "and"` on the same conditions.
4. WHEN a rule definition omits the `combinator` field, THE RuleEvaluator SHALL default to `"and"` semantics.

---

### Requirement 7: Rule Evaluator — Operators

**User Story:** As a system architect, I want the rules engine to support a comprehensive set of comparison operators, so that conditions can be expressed against numeric, string, and list field values.

#### Acceptance Criteria

1. THE RuleEvaluator SHALL support the `equals` operator, returning `true` when the field value is strictly equal to the rule value.
2. THE RuleEvaluator SHALL support the `not_equals` operator, returning `true` when the field value is not equal to the rule value.
3. THE RuleEvaluator SHALL support the `in` operator, returning `true` when the field value is contained within the rule value array.
4. THE RuleEvaluator SHALL support the `not_in` operator, returning `true` when the field value is not contained within the rule value array.
5. THE RuleEvaluator SHALL support the `gte` operator, returning `true` when the field value is greater than or equal to the rule value.
6. THE RuleEvaluator SHALL support the `lte` operator, returning `true` when the field value is less than or equal to the rule value.
7. THE RuleEvaluator SHALL support the `gt` operator, returning `true` when the field value is strictly greater than the rule value.
8. THE RuleEvaluator SHALL support the `lt` operator, returning `true` when the field value is strictly less than the rule value.
9. THE RuleEvaluator SHALL support the `contains` operator, returning `true` when the rule value is contained within the field value (string or array).
10. THE RuleEvaluator SHALL support the `not_contains` operator, returning `true` when the rule value is not contained within the field value.
11. WHEN an operator string is provided that does not match any of the 10 defined operators, THE RuleEvaluator SHALL throw `InvalidRuleDefinitionException` rather than silently returning `false`.

---

### Requirement 8: Evaluation Context

**User Story:** As a system architect, I want the evaluation context to be built exclusively from server-side data, so that rule evaluation cannot be manipulated by user-supplied request input.

#### Acceptance Criteria

1. THE EvaluationContext SHALL include the field `plan` containing the tenant's current pricing plan key (e.g. `"professional"`).
2. THE EvaluationContext SHALL include the field `employee_count` containing the number of active employees for the tenant.
3. THE EvaluationContext SHALL include the field `user_role` containing the authenticated user's primary role name as defined by Spatie Laravel Permission.
4. THE EvaluationContext SHALL include the field `module_key` containing the subject module key being evaluated.
5. THE EvaluationContext SHALL include the field `date` containing the current date in ISO 8601 format (e.g. `"2025-07-14"`).
6. THE EvaluationContext SHALL include the field `day_of_week` containing an integer from 0 (Sunday) to 6 (Saturday).
7. THE EvaluationContext SHALL be constructed from `Auth::user()`, the tenant database record, and `config/pricing.php` only — never from user-supplied HTTP request parameters.

---

### Requirement 9: HTTP Middleware — Module Gate

**User Story:** As a developer, I want HTTP middleware to enforce module access control on route groups, so that disabled modules return a consistent error response without requiring per-component checks.

#### Acceptance Criteria

1. WHEN `ModuleActiveMiddleware` is applied to a route and `ModuleRegistry::isActive()` returns `false` for the resolved module key, THE ModuleActiveMiddleware SHALL return an HTTP 403 response rendering the `errors.module-disabled` Blade view.
2. WHEN `ModuleActiveMiddleware` is applied to a route and `ModuleRegistry::isActive()` returns `true`, THE ModuleActiveMiddleware SHALL pass the request to the next middleware in the pipeline.
3. THE ModuleActiveMiddleware SHALL resolve the module key from the route's middleware parameter (e.g. `middleware('module:payroll')`).

---

### Requirement 10: HTTP Middleware — Feature Gate

**User Story:** As a developer, I want HTTP middleware to enforce feature flag access control on route groups, so that disabled features return a consistent error response.

#### Acceptance Criteria

1. WHEN `FeatureFlagMiddleware` is applied to a route and `FeatureFlagService::isEnabled()` returns `false` for the resolved flag key, THE FeatureFlagMiddleware SHALL return an HTTP 403 response.
2. WHEN `FeatureFlagMiddleware` is applied to a route and `FeatureFlagService::isEnabled()` returns `true`, THE FeatureFlagMiddleware SHALL pass the request to the next middleware in the pipeline.
3. THE FeatureFlagMiddleware SHALL be applied after `ModuleActiveMiddleware` in the middleware stack, so that a disabled module short-circuits before feature flag evaluation is attempted.

---

### Requirement 11: Layered Access Control Invariant

**User Story:** As a security engineer, I want the module gate to be a strict superset of the feature gate, so that a disabled module can never be bypassed by enabling a feature flag within it.

#### Acceptance Criteria

1. WHILE `ModuleRegistry::isActive()` returns `false` for module `M`, THE FeatureFlagService SHALL also return `false` for any flag key prefixed with `M.` (enforced by middleware ordering and service composition).
2. THE system SHALL enforce the middleware order: `ModuleActiveMiddleware` MUST execute before `FeatureFlagMiddleware` on any route that applies both.

---

### Requirement 12: Cache Resilience

**User Story:** As a system operator, I want the services to degrade gracefully when the cache backend is unavailable, so that a cache outage does not cause an application outage.

#### Acceptance Criteria

1. WHEN the Laravel cache backend throws an exception during a `get()` or `put()` call, THE ModuleRegistry SHALL catch the exception and fall through to a direct database query to determine the result.
2. WHEN the Laravel cache backend throws an exception during a `get()` or `put()` call, THE FeatureFlagService SHALL catch the exception and fall through to a direct database query to determine the result.
3. WHEN the Laravel cache backend throws an exception during a `get()` or `put()` call, THE RuleService SHALL catch the exception and fall through to a direct database query to load rules.

---

### Requirement 13: Admin Access Control

**User Story:** As a security engineer, I want module and feature toggle operations to be restricted to authorised administrators, so that tenants cannot escalate their own access.

#### Acceptance Criteria

1. WHEN a user without the `manage-modules` Spatie permission attempts to call a module or feature toggle endpoint, THE system SHALL return an HTTP 403 response.
2. THE `ModuleRegistry::enable()`, `ModuleRegistry::disable()`, `FeatureFlagService::enable()`, `FeatureFlagService::disable()`, and `RuleService::upsert()` methods SHALL be protected by a `permission:manage-modules` gate check.

---

### Requirement 14: Rule Management

**User Story:** As a tenant administrator, I want to create, update, and remove rules for modules and features, so that I can define custom access conditions without modifying code.

#### Acceptance Criteria

1. WHEN `RuleService::upsert()` is called with a subject, rule key, and definition array, THE RuleService SHALL insert a new row or update the existing row in `module_rules` matching that subject and rule key.
2. WHEN `RuleService::remove()` is called with a subject and rule key, THE RuleService SHALL delete the matching row from `module_rules`.
3. WHEN a rule is upserted or removed, THE RuleService SHALL invalidate the cache key `module_rules:{tenantId}:{subject}` before the method returns.
4. THE `rule_key` field in `module_rules` SHALL be unique across the table to prevent duplicate rule identifiers.
