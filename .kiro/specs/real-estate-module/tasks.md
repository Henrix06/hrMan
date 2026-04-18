# Implementation Plan: Real Estate Module

## Overview

Implement the Real Estate Module as a Laravel feature integrated into the existing multi-tenant HR/ERP system. The plan follows the established codebase conventions: Eloquent models, Action classes, Livewire components, Spatie RBAC, and the `TenantModule` registry. Tasks are sequenced so each step builds on the previous, ending with full wiring and scheduler registration.

## Tasks

- [x] 1. Database migrations and module registration
  - Create migration `create_real_estate_tables.php` with all five tables: `re_properties`, `re_tenants`, `re_leases`, `re_rent_invoices`, `re_maintenance_requests` — including all indexes, unique constraints, and foreign keys defined in the design
  - Enforce unique constraint on `(lease_id, billing_period)` in `re_rent_invoices`
  - Register the `real_estate` module key in `bootstrap/cache/modules.php` (or equivalent `TenantModule` seeder/migration) so `ModuleActiveMiddleware` can gate it
  - Seed the four Spatie permissions: `real_estate.view`, `real_estate.manage_leases`, `real_estate.manage_maintenance`, `real_estate.manage_invoices`
  - _Requirements: 1.1, 1.2, 8.1, 8.2, 10.2, 10.3, 10.6_

- [ ] 2. Eloquent models
  - [x] 2.1 Create `app/Models/RealEstate/Property.php`
    - Table `re_properties`, typed casts, fillable, soft deletes
    - Relationships: `hasMany(Lease)`, `hasMany(MaintenanceRequest)`, `belongsTo(User, 'created_by')`
    - Global scope filtering by authenticated `tenant_id`
    - `generateReference('PROP')` helper (format `PROP-YYYY-NNNNN`)
    - _Requirements: 1.1, 1.2, 1.8, 10.1_

  - [x] 2.2 Create `app/Models/RealEstate/RealEstateTenant.php`
    - Table `re_tenants`, typed casts, fillable, soft deletes
    - Relationships: `hasMany(Lease)`, `belongsTo(User, 'user_id')`
    - Global scope filtering by authenticated `company_tenant_id`
    - `generateReference('TEN')` helper
    - _Requirements: 2.1, 2.2, 2.6, 10.1_

  - [x] 2.3 Create `app/Models/RealEstate/Lease.php`
    - Table `re_leases`, typed casts, fillable, soft deletes
    - Relationships: `belongsTo(Property)`, `belongsTo(RealEstateTenant, 're_tenant_id')`, `belongsTo(Account)`, `hasMany(RentInvoice)`, `hasMany(MaintenanceRequest)`
    - Scopes: `active()`, `dueOn(Carbon $date)`, `expiringSoon(int $days)`
    - `generateReference('LSE')` helper
    - _Requirements: 3.1, 3.10, 3.11, 10.1_

  - [x] 2.4 Create `app/Models/RealEstate/RentInvoice.php`
    - Table `re_rent_invoices`, typed casts, fillable (no soft deletes)
    - Relationships: `belongsTo(Lease)`, `belongsTo(AccountTransaction, 'transaction_id')`
    - Scopes: `overdue()`, `pendingPenalty(Carbon $asOf)`
    - `generateReference('RNT')` helper
    - _Requirements: 4.2, 4.3, 10.1, 10.3_

  - [x] 2.5 Create `app/Models/RealEstate/MaintenanceRequest.php`
    - Table `re_maintenance_requests`, typed casts, fillable (no soft deletes)
    - Relationships: `belongsTo(Property)`, `belongsTo(Lease)`, `belongsTo(RealEstateTenant, 're_tenant_id')`, `belongsTo(User, 'assigned_to')`, `belongsTo(User, 'created_by')`
    - `generateReference('MNT')` helper
    - _Requirements: 6.1, 10.1_

  - [ ]* 2.6 Write property test for reference generation (Property 1)
    - **Property 1: Reference format and uniqueness**
    - Generate N references for each entity type; assert all match `PREFIX-YYYY-NNNNN` and are unique within type
    - **Validates: Requirements 1.1, 2.1, 3.1, 4.2, 6.1, 10.1, 10.2**

- [x] 3. Custom exceptions
  - Create `app/Exceptions/RealEstate/LeaseActivationException.php`
  - Create `app/Exceptions/RealEstate/LeaseCreationException.php`
  - _Requirements: 3.4, 3.5, 2.4, 2.5_

- [ ] 4. LeaseService
  - [x] 4.1 Create `app/Services/RealEstate/LeaseService.php` with `create()` method
    - Validate property status is `available` or `partially_let`
    - Validate tenant status is not `blacklisted` or `inactive`
    - Generate reference, persist Lease with `status = draft`
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 4.2 Implement `LeaseService::activate()`
    - Assert `status === 'draft'`; throw `LeaseActivationException` otherwise
    - Assert deposit guard: if `deposit_amount > 0` and `deposit_paid = false`, throw `LeaseActivationException`
    - Set `status = active`, update Property status to `partially_let` or `fully_let`
    - _Requirements: 3.4, 3.5, 3.6, 1.5, 1.6_

  - [ ]* 4.3 Write property test for deposit guard (Property 5)
    - **Property 5: Lease activation precondition — deposit guard**
    - For any draft Lease with `deposit_amount > 0` and `deposit_paid = false`, `activate()` must throw and leave status unchanged
    - **Validates: Requirements 3.4, 3.5**

  - [ ]* 4.4 Write property test for property status invariant (Property 2)
    - **Property 2: Property status reflects active lease occupancy**
    - For any combination of K active leases and N units, assert correct `available` / `partially_let` / `fully_let` status
    - **Validates: Requirements 1.5, 1.6, 1.7, 3.6**

  - [x] 4.5 Implement `LeaseService::renew()`
    - Set existing Lease `status = renewed`
    - Create new Lease with `status = active` and new terms
    - Assert exactly one active Lease per property unit after renewal
    - _Requirements: 3.7, 3.11_

  - [ ]* 4.6 Write property test for lease renewal round-trip (Property 7)
    - **Property 7: Lease renewal round-trip**
    - After `renew()`, original lease is `renewed`, new lease is `active`, total active leases per unit = 1
    - **Validates: Requirements 3.7**

  - [x] 4.7 Implement `LeaseService::terminate()` and `getExpiringSoon()`
    - `terminate()`: set `status = terminated`, record `terminated_at` and `termination_reason`, update Property status
    - `getExpiringSoon(int $days)`: return leases with `end_date` within `$days` days
    - _Requirements: 3.8, 3.9, 3.10, 1.7_

  - [ ]* 4.8 Write property test for no duplicate active leases (Property 6)
    - **Property 6: No duplicate active leases per property unit**
    - After any sequence of create/activate/renew/terminate operations, assert no property unit has more than one active lease
    - **Validates: Requirements 3.11**

  - [ ]* 4.9 Write unit tests for LeaseService
    - Test all precondition branches: blacklisted tenant, inactive tenant, wrong property status, wrong lease status
    - Test `getExpiringSoon()` boundary conditions
    - _Requirements: 2.4, 2.5, 3.2, 3.3, 3.4, 3.5_

- [x] 5. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. RentInvoicingService
  - [x] 6.1 Create `app/Services/RealEstate/RentInvoicingService.php` with `generateDueInvoices(Carbon $billingDate)`
    - Query active leases with matching `payment_due_day`
    - Idempotency guard: skip if invoice already exists for `(lease_id, billing_period)`
    - Wrap each invoice creation + `DebitAccount` call in a DB transaction
    - If `account_id` is null, create invoice with `status = invoiced` and log warning without calling `DebitAccount`
    - _Requirements: 4.1, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 10.4, 10.5_

  - [ ]* 6.2 Write property test for invoice generation idempotence (Property 8)
    - **Property 8: Invoice generation idempotence**
    - Call `generateDueInvoices()` twice with the same date; assert invoice count is identical and no duplicates exist
    - **Validates: Requirements 4.8, 10.3**

  - [ ]* 6.3 Write property test for invoices only for active leases (Property 9)
    - **Property 9: Invoices generated only for active leases**
    - For any billing date, assert no RentInvoice is created for a non-active lease
    - **Validates: Requirements 4.9**

  - [ ]* 6.4 Write property test for graceful missing account (Property 17)
    - **Property 17: Invoice generation with missing account is graceful**
    - For any active Lease with `account_id = null`, assert invoice is created with `status = invoiced` and `DebitAccount` is not called
    - **Validates: Requirements 4.7**

  - [x] 6.5 Implement `RentInvoicingService::markPaid()`
    - Validate invoice status is `invoiced` or `overdue`; reject otherwise
    - Set `status = paid`, record `paid_at`, call `CreditAccount`
    - Wrap in DB transaction
    - _Requirements: 4.10, 4.11, 10.4_

  - [ ]* 6.6 Write unit tests for RentInvoicingService
    - Test `markPaid()` rejection when status is not `invoiced`/`overdue`
    - Test transaction rollback on `DebitAccount` failure
    - _Requirements: 4.10, 4.11, 10.5_

- [ ] 7. PenaltyService
  - [x] 7.1 Create `app/Services/RealEstate/PenaltyService.php` with `calculatePenalty()`
    - Formula: `rent_amount × (penalty_rate / 100) × MAX(1, CEIL(days_overdue / 30))`
    - Return value must be >= 0
    - _Requirements: 5.3, 5.10_

  - [ ]* 7.2 Write property test for penalty non-negativity (Property 11)
    - **Property 11: Penalty non-negativity**
    - For any valid invoice and date, `calculatePenalty()` returns a value >= 0
    - **Validates: Requirements 5.10**

  - [x] 7.3 Implement `PenaltyService::applyPendingPenalties(Carbon $asOf)`
    - Query invoices with status in `['invoiced', 'overdue']` and `due_date < $asOf`
    - Skip invoices still within grace period (`due_date + grace_period_days >= $asOf`)
    - Accumulate `penalty_amount` (additive), update `total_amount = rent_amount + penalty_amount`, set `status = overdue`
    - Call `DebitAccount` with category `late_penalty` if `account_id` is set
    - Wrap each invoice update + ledger call in a DB transaction; log and continue on failure
    - _Requirements: 5.1, 5.2, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 10.4, 10.5_

  - [ ]* 7.4 Write property test for penalty grace period guard (Property 12)
    - **Property 12: Penalty grace period guard**
    - For any invoice where `asOf <= due_date + grace_period_days`, assert `penalty_amount` and `total_amount` are unchanged after `applyPendingPenalties()`
    - **Validates: Requirements 5.2**

  - [ ]* 7.5 Write property test for penalty monotonic accumulation (Property 13)
    - **Property 13: Penalty amount is monotonically non-decreasing**
    - After any number of `applyPendingPenalties()` calls, assert `penalty_amount` never decreases
    - **Validates: Requirements 5.7**

  - [ ]* 7.6 Write property test for total_amount invariant (Property 10)
    - **Property 10: Invoice total_amount invariant**
    - After any penalty application, assert `total_amount === rent_amount + penalty_amount` for all processed invoices
    - **Validates: Requirements 4.5, 5.4, 5.8**

  - [ ]* 7.7 Write unit tests for PenaltyService
    - Test grace period boundary (last day in grace, first day past grace)
    - Test one-penalty-per-day guard
    - _Requirements: 5.2, 5.9_

- [x] 8. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. MaintenanceService
  - [x] 9.1 Create `app/Services/RealEstate/MaintenanceService.php` with `create()`
    - Validate linked Lease (if provided) has `status = active`; throw `ValidationException` otherwise
    - Generate reference, persist with `status = open`
    - Send `HrNotification` to property manager
    - _Requirements: 6.1, 6.2, 6.3_

  - [ ]* 9.2 Write property test for maintenance on inactive lease (Property 15)
    - **Property 15: Maintenance request on inactive lease is rejected**
    - For any Lease with status other than `active`, assert `create()` throws and no record is persisted
    - **Validates: Requirements 6.3**

  - [x] 9.3 Implement `MaintenanceService::assign()`
    - Validate `status === 'open'`; reject otherwise
    - Set `status = in_progress`, `assigned_to`, `assigned_at`
    - _Requirements: 6.4, 6.5_

  - [x] 9.4 Implement `MaintenanceService::resolve()` and `close()`
    - `resolve()`: validate `status === 'in_progress'`, set `status = resolved`, `resolved_at`, `resolution_notes`, optional `repair_cost`, notify tenant via `HrNotification`
    - `close()`: validate `status === 'resolved'`; reject otherwise; set `status = closed`
    - _Requirements: 6.6, 6.7, 6.8, 6.9_

  - [ ]* 9.5 Write property test for maintenance lifecycle state machine (Property 14)
    - **Property 14: Maintenance request lifecycle state machine**
    - Assert valid transitions `open → in_progress → resolved → closed` succeed; assert invalid skips (e.g., `open → closed`) are rejected
    - **Validates: Requirements 6.4, 6.5, 6.9**

  - [x] 9.6 Implement cancellation support in `MaintenanceService`
    - Allow cancellation from any non-closed, non-resolved status; set `status = cancelled`
    - _Requirements: 6.10_

  - [ ]* 9.7 Write unit tests for MaintenanceService
    - Test all invalid state transition rejections
    - Test notification dispatch for create and resolve
    - _Requirements: 6.2, 6.5, 6.7, 6.9_

- [ ] 10. Scheduled jobs
  - [x] 10.1 Create `app/Jobs/RealEstate/GenerateRentInvoicesJob.php`
    - Implement `ShouldQueue`, inject `RentInvoicingService`, call `generateDueInvoices($this->billingDate)`
    - Wrap per-lease processing in try/catch; log errors and continue
    - _Requirements: 4.1, 7.1, 7.5_

  - [x] 10.2 Create `app/Jobs/RealEstate/ApplyLatePenaltiesJob.php`
    - Implement `ShouldQueue`, inject `PenaltyService`, call `applyPendingPenalties(Carbon::today())`
    - Wrap per-invoice processing in try/catch; log errors and continue
    - _Requirements: 5.1, 7.2, 7.6_

  - [x] 10.3 Register both jobs in `routes/console.php`
    - `GenerateRentInvoicesJob`: `->monthlyOn(1, '08:00')->withoutOverlapping()->onOneServer()`
    - `ApplyLatePenaltiesJob`: `->dailyAt('09:00')->withoutOverlapping()->onOneServer()`
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ]* 10.4 Write unit tests for scheduled jobs
    - Test that `GenerateRentInvoicesJob` calls `generateDueInvoices()` with the correct date
    - Test that `ApplyLatePenaltiesJob` calls `applyPendingPenalties()` with today's date
    - _Requirements: 7.1, 7.2_

- [ ] 11. Access control and tenant scoping
  - [x] 11.1 Create `app/Policies/RealEstate/LeasePolicy.php`
    - Gate `create`, `activate`, `renew`, `terminate` on `real_estate.manage_leases`
    - _Requirements: 8.3_

  - [x] 11.2 Create `app/Policies/RealEstate/RentInvoicePolicy.php`
    - Gate `markPaid` on `real_estate.manage_invoices`
    - _Requirements: 8.4_

  - [x] 11.3 Create `app/Policies/RealEstate/MaintenanceRequestPolicy.php`
    - Gate `assign`, `resolve`, `close` on `real_estate.manage_maintenance`
    - _Requirements: 8.5_

  - [x] 11.4 Apply portal-user scoping in Lease and MaintenanceRequest queries
    - When the authenticated user is a portal user (linked `RealEstateTenant`), restrict query results to their `re_tenant_id`
    - _Requirements: 2.7, 8.7_

  - [ ]* 11.5 Write property test for tenant scoping (Property 3)
    - **Property 3: Tenant scoping — no cross-tenant data leakage**
    - Seed two company tenants with overlapping data; assert each tenant's queries return only their own records
    - **Validates: Requirements 1.8, 2.6, 8.6**

  - [ ]* 11.6 Write property test for portal user data scoping (Property 16)
    - **Property 16: Portal user data scoping**
    - For any portal user linked to a RealEstateTenant, assert Lease and MaintenanceRequest queries return only records for their `re_tenant_id`
    - **Validates: Requirements 2.7, 8.7**

  - [ ]* 11.7 Write property test for blacklisted tenant blocks lease creation (Property 4)
    - **Property 4: Blacklisted tenant blocks lease creation**
    - For any RealEstateTenant with `status = blacklisted`, assert every `LeaseService::create()` attempt is rejected and no Lease is persisted
    - **Validates: Requirements 2.4, 2.5, 3.3**

- [x] 12. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Livewire components — Properties and Tenants
  - [x] 13.1 Create `app/Livewire/RealEstate/PropertyList.php` and `resources/views/livewire/real-estate/property-list.blade.php`
    - Browse and filter properties by status, type, city
    - Create new Property via modal form; display validation errors inline
    - Gate actions with `real_estate.view` / `real_estate.manage_leases`
    - _Requirements: 9.1, 8.2, 9.7_

  - [x] 13.2 Create `app/Livewire/RealEstate/PropertyDetail.php` and corresponding Blade view
    - Display property details, active Leases, and open MaintenanceRequests
    - _Requirements: 9.2_

  - [x] 13.3 Create `app/Livewire/RealEstate/TenantList.php` and corresponding Blade view
    - Browse, filter, and manage RealEstateTenant records
    - Display validation errors inline on form submission failure
    - _Requirements: 9.4, 9.7_

- [ ] 14. Livewire components — Leases and Invoices
  - [x] 14.1 Create `app/Livewire/RealEstate/LeaseManager.php` and corresponding Blade view
    - Support create, activate, renew, and terminate lease actions
    - Display validation errors inline; gate actions with `real_estate.manage_leases`
    - _Requirements: 9.3, 8.3, 9.7_

  - [x] 14.2 Create `app/Livewire/RealEstate/RentInvoiceList.php` and corresponding Blade view
    - Display RentInvoices filterable by status
    - Mark invoice as paid action; gate with `real_estate.manage_invoices`
    - Display validation errors inline
    - _Requirements: 9.5, 8.4, 9.7_

- [x] 15. Livewire component — MaintenanceBoard
  - Create `app/Livewire/RealEstate/MaintenanceBoard.php` and corresponding Blade view
  - Kanban-style board grouped by status: `open`, `in_progress`, `pending_parts`, `resolved`, `closed`
  - Support assign, resolve, close, and cancel actions; gate with `real_estate.manage_maintenance`
  - Display validation errors inline
  - _Requirements: 9.6, 8.5, 9.7_

- [x] 16. Route registration and middleware wiring
  - Add real estate routes to `routes/web.php` (or a dedicated `routes/real-estate.php` included from `web.php`)
  - Apply `middleware(['auth', 'module:real_estate'])` to all real estate routes
  - Register all Livewire components in `AppServiceProvider` or via auto-discovery
  - _Requirements: 8.1_

- [x] 17. Final checkpoint — Ensure all tests pass
  - Run the full test suite; ensure all unit, property, and integration tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Each task references specific requirements for traceability
- Property tests use PestPHP; each property sub-task maps to a numbered correctness property from `design.md`
- All financial operations (invoice creation, penalty application, mark-paid) must be wrapped in DB transactions per Requirements 10.4 and 10.5
- The `generateReference()` helper should be extracted to a shared trait (e.g., `HasRealEstateReference`) reused across all five models
