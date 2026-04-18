# Requirements Document

## Introduction

The Real Estate Module extends the existing Laravel multi-tenant HR/ERP system to manage commercial and residential properties, lease agreements, real estate tenant relationships, automated monthly rent invoicing, late-payment penalty enforcement, and maintenance request tracking. The module integrates with the existing accounts ledger (via `DebitAccount` / `CreditAccount` actions), the `TenantModule` registry for per-tenant feature gating, Spatie RBAC for access control, and Livewire for all UI interactions.

---

## Glossary

- **System**: The Real Estate Module as a whole.
- **Property**: A lettable real estate asset (residential, commercial, or industrial) owned by a company tenant, stored in `re_properties`.
- **RealEstateTenant**: A person or company that rents a property unit, stored in `re_tenants`. Distinct from the multi-tenant `User`/`TenantModule` concept.
- **Lease**: A formal rental agreement between a Property and a RealEstateTenant, stored in `re_leases`.
- **RentInvoice**: A monthly billing record generated for an active Lease, stored in `re_rent_invoices`.
- **MaintenanceRequest**: A repair or service request raised against a Property or Lease, stored in `re_maintenance_requests`.
- **LeaseService**: The service class responsible for Lease lifecycle operations (create, activate, renew, terminate).
- **RentInvoicingService**: The service class responsible for generating monthly RentInvoices and recording ledger entries.
- **PenaltyService**: The service class responsible for calculating and applying late-payment penalties to overdue RentInvoices.
- **MaintenanceService**: The service class responsible for the MaintenanceRequest lifecycle (create, assign, resolve, close).
- **GenerateRentInvoicesJob**: A queued job that calls `RentInvoicingService::generateDueInvoices()` on the 1st of each month at 08:00.
- **ApplyLatePenaltiesJob**: A queued job that calls `PenaltyService::applyPendingPenalties()` daily at 09:00.
- **DebitAccount**: The existing `App\Actions\Accounts\DebitAccount` action that records a debit ledger entry.
- **CreditAccount**: The existing `App\Actions\Accounts\CreditAccount` action that records a credit ledger entry.
- **ModuleRegistry**: The `TenantModule`-backed registry that gates module access per company tenant.
- **Actor**: The authenticated `User` performing an operation.
- **GracePeriod**: The number of days after a RentInvoice due date before a late-payment penalty is applied.
- **BillingPeriod**: The first day of the calendar month for which a RentInvoice is generated.
- **PenaltyRate**: The percentage of `rent_amount` charged per month overdue, stored on the Lease.

---

## Requirements

### Requirement 1: Property Management

**User Story:** As a property manager, I want to create and manage property records, so that I can track all lettable assets owned by my organisation.

#### Acceptance Criteria

1. WHEN a property manager submits valid property data, THE System SHALL create a Property record with a unique reference in the format `PROP-YYYY-NNNNN` and an initial status of `available`.
2. THE System SHALL enforce that each Property `reference` is unique across all records.
3. WHEN a property manager updates a Property, THE System SHALL persist the changes and maintain the existing `reference`.
4. WHEN a property manager soft-deletes a Property, THE System SHALL set the `deleted_at` timestamp and exclude the record from default queries.
5. WHILE a Property has one or more active Leases covering fewer than all units, THE System SHALL maintain the Property `status` as `partially_let`.
6. WHILE all units of a Property are covered by active Leases, THE System SHALL maintain the Property `status` as `fully_let`.
7. WHEN all active Leases on a Property are terminated or expired, THE System SHALL set the Property `status` to `available`.
8. THE System SHALL scope all Property queries to the authenticated company `tenant_id`, ensuring no cross-tenant data leakage.
9. WHEN a user without the `real_estate.view` permission attempts to access Property records, THE System SHALL deny access and return an authorisation error.

---

### Requirement 2: Real Estate Tenant Management

**User Story:** As a property manager, I want to create and manage real estate tenant profiles, so that I can track who is renting my properties.

#### Acceptance Criteria

1. WHEN a property manager submits valid tenant data, THE System SHALL create a RealEstateTenant record with a unique reference in the format `TEN-YYYY-NNNNN` and an initial status of `active`.
2. THE System SHALL enforce that each RealEstateTenant `reference` is unique across all records.
3. WHEN a RealEstateTenant is linked to a portal `User`, THE System SHALL associate the `user_id` foreign key on the RealEstateTenant record.
4. WHEN a property manager sets a RealEstateTenant status to `blacklisted`, THE System SHALL prevent any new Lease from being created for that RealEstateTenant.
5. IF a RealEstateTenant has status `blacklisted` and a Lease creation is attempted, THEN THE LeaseService SHALL reject the request and return a validation error.
6. THE System SHALL scope all RealEstateTenant queries to the authenticated company `tenant_id`.
7. WHEN a portal user accesses RealEstateTenant data, THE System SHALL restrict the visible records to the RealEstateTenant linked to that user's `user_id`.

---

### Requirement 3: Lease Lifecycle Management

**User Story:** As a property manager, I want to manage the full lifecycle of lease agreements, so that I can track rental terms, activate leases, handle renewals, and record terminations.

#### Acceptance Criteria

1. WHEN a property manager submits valid lease data, THE LeaseService SHALL create a Lease record with status `draft` and a unique reference in the format `LSE-YYYY-NNNNN`.
2. IF a Lease creation is attempted for a Property that is not `available` or `partially_let`, THEN THE LeaseService SHALL reject the request with a validation error.
3. IF a Lease creation is attempted for a RealEstateTenant with status `blacklisted` or `inactive`, THEN THE LeaseService SHALL reject the request with a validation error.
4. WHEN a property manager activates a Lease, THE LeaseService SHALL verify that the Lease has status `draft` before proceeding.
5. IF a Lease activation is attempted and `deposit_amount > 0` and `deposit_paid = false`, THEN THE LeaseService SHALL reject the activation and return a validation error.
6. WHEN a Lease is successfully activated, THE LeaseService SHALL set the Lease `status` to `active` and update the associated Property `status` to `partially_let` or `fully_let` as appropriate.
7. WHEN a property manager renews an active Lease, THE LeaseService SHALL set the existing Lease `status` to `renewed` and create a new Lease record with `status = active` reflecting the new terms.
8. WHEN a property manager terminates a Lease, THE LeaseService SHALL set the Lease `status` to `terminated`, record the `terminated_at` date, store the `termination_reason`, and update the Property `status`.
9. WHEN a Lease `end_date` passes without renewal or termination, THE System SHALL transition the Lease `status` to `expired` and stop generating new RentInvoices for it.
10. THE LeaseService SHALL expose a query that returns all Leases whose `end_date` falls within a configurable number of days from today, for renewal reminder purposes.
11. THE System SHALL ensure that at any point in time, no Property unit has more than one Lease with `status = active`.

---

### Requirement 4: Automated Rent Invoicing

**User Story:** As a property manager, I want rent invoices to be generated automatically each month, so that billing is consistent and requires no manual intervention.

#### Acceptance Criteria

1. WHEN the `GenerateRentInvoicesJob` is dispatched on the 1st of each month at 08:00, THE RentInvoicingService SHALL generate one RentInvoice for each active Lease whose `payment_due_day` matches the billing date's day-of-month.
2. THE RentInvoicingService SHALL assign each generated RentInvoice a unique reference in the format `RNT-YYYY-NNNNN`.
3. THE RentInvoicingService SHALL set the `billing_period` of each RentInvoice to the first day of the billed calendar month.
4. THE RentInvoicingService SHALL set the `rent_amount` of each RentInvoice equal to the Lease `monthly_rent` at the time of generation.
5. THE RentInvoicingService SHALL set the initial `total_amount` of each RentInvoice equal to `rent_amount` (with `penalty_amount = 0`).
6. WHEN a RentInvoice is created and the Lease has an associated `account_id`, THE RentInvoicingService SHALL call `DebitAccount` to record a ledger entry with category `rent`.
7. IF a Lease has no associated `account_id`, THEN THE RentInvoicingService SHALL create the RentInvoice with `status = invoiced` and log a warning, without calling `DebitAccount`.
8. THE RentInvoicingService SHALL be idempotent: calling `generateDueInvoices` twice with the same billing date SHALL produce no duplicate RentInvoices (enforced by the unique constraint on `(lease_id, billing_period)`).
9. THE RentInvoicingService SHALL not generate a RentInvoice for any Lease with `status` other than `active`.
10. WHEN a property manager marks a RentInvoice as paid, THE RentInvoicingService SHALL set the invoice `status` to `paid`, record the `paid_at` date, and call `CreditAccount` to record a ledger credit transaction.
11. IF a RentInvoice `status` is not `invoiced` or `overdue` when a mark-paid request is made, THEN THE RentInvoicingService SHALL reject the request with a validation error.

---

### Requirement 5: Late Payment Penalty Enforcement

**User Story:** As a property manager, I want late payment penalties to be applied automatically to overdue invoices, so that tenants are incentivised to pay on time.

#### Acceptance Criteria

1. WHEN the `ApplyLatePenaltiesJob` runs daily at 09:00, THE PenaltyService SHALL evaluate all RentInvoices with `status` in `['invoiced', 'overdue']` and `due_date < today`.
2. WHILE a RentInvoice `due_date` plus the Lease `grace_period_days` is greater than or equal to today, THE PenaltyService SHALL not apply a penalty to that invoice.
3. WHEN a RentInvoice has passed its grace period, THE PenaltyService SHALL calculate the penalty as `rent_amount × (penalty_rate / 100) × months_overdue`, where `months_overdue = MAX(1, CEIL(days_overdue / 30))`.
4. WHEN a penalty is calculated and is greater than zero, THE PenaltyService SHALL add the penalty to the invoice `penalty_amount` (accumulating, not replacing) and update `total_amount = rent_amount + penalty_amount`.
5. WHEN a penalty is applied and the Lease has an associated `account_id`, THE PenaltyService SHALL call `DebitAccount` with category `late_penalty` to record the ledger entry.
6. THE PenaltyService SHALL set the RentInvoice `status` to `overdue` when a penalty is applied.
7. THE PenaltyService SHALL ensure that `penalty_amount` is non-decreasing across successive penalty applications (penalties accumulate and are never reduced by the penalty job).
8. THE PenaltyService SHALL ensure that `total_amount === rent_amount + penalty_amount` holds after every penalty application.
9. THE PenaltyService SHALL apply at most one penalty increment per RentInvoice per calendar day.
10. IF `calculatePenalty` is called with any valid invoice and date combination, THEN THE PenaltyService SHALL return a value greater than or equal to zero.

---

### Requirement 6: Maintenance Request Management

**User Story:** As a tenant or property manager, I want to submit and track maintenance requests, so that property issues are resolved in a timely and auditable manner.

#### Acceptance Criteria

1. WHEN a tenant or property manager submits a maintenance request with valid data, THE MaintenanceService SHALL create a MaintenanceRequest record with `status = open` and a unique reference in the format `MNT-YYYY-NNNNN`.
2. WHEN a MaintenanceRequest is created, THE MaintenanceService SHALL send an in-app notification to the property manager via `HrNotification`.
3. IF a maintenance request is submitted against a Lease with `status` other than `active`, THEN THE MaintenanceService SHALL reject the request with a validation error.
4. WHEN a property manager assigns a MaintenanceRequest to a maintenance worker, THE MaintenanceService SHALL set `status = in_progress`, record `assigned_to`, and record `assigned_at`.
5. IF an assignment is attempted on a MaintenanceRequest with `status` other than `open`, THEN THE MaintenanceService SHALL reject the assignment with a validation error.
6. WHEN a maintenance worker resolves a MaintenanceRequest, THE MaintenanceService SHALL set `status = resolved`, record `resolved_at`, store `resolution_notes`, and optionally record `repair_cost`.
7. WHEN a MaintenanceRequest is resolved, THE MaintenanceService SHALL send an in-app notification to the tenant via `HrNotification`.
8. WHEN a property manager closes a resolved MaintenanceRequest, THE MaintenanceService SHALL set `status = closed`.
9. IF a close action is attempted on a MaintenanceRequest with `status` other than `resolved`, THEN THE MaintenanceService SHALL reject the action with a validation error.
10. THE System SHALL allow a MaintenanceRequest to be cancelled from any non-closed, non-resolved status, setting `status = cancelled`.

---

### Requirement 7: Scheduled Job Execution

**User Story:** As a system administrator, I want scheduled jobs to run reliably and without overlap, so that billing and penalty operations are consistent and auditable.

#### Acceptance Criteria

1. THE System SHALL schedule `GenerateRentInvoicesJob` to run on the 1st of every month at 08:00 using the Laravel Scheduler.
2. THE System SHALL schedule `ApplyLatePenaltiesJob` to run daily at 09:00 using the Laravel Scheduler.
3. THE System SHALL configure both scheduled jobs with `withoutOverlapping()` to prevent concurrent executions.
4. THE System SHALL configure both scheduled jobs with `onOneServer()` to prevent duplicate execution in multi-server deployments.
5. WHEN `GenerateRentInvoicesJob` fails for a specific lease, THE System SHALL log the error and continue processing remaining leases without aborting the entire job.
6. WHEN `ApplyLatePenaltiesJob` fails for a specific invoice, THE System SHALL log the error and continue processing remaining invoices without aborting the entire job.

---

### Requirement 8: Access Control and Multi-Tenancy

**User Story:** As a system administrator, I want all real estate data and operations to be protected by role-based access control and tenant scoping, so that data is secure and isolated.

#### Acceptance Criteria

1. THE System SHALL register the module under the key `real_estate` in the `ModuleRegistry` and gate all module routes with `ModuleActiveMiddleware`.
2. THE System SHALL define the following Spatie permissions: `real_estate.view`, `real_estate.manage_leases`, `real_estate.manage_maintenance`, `real_estate.manage_invoices`.
3. WHEN a user without the `real_estate.manage_leases` permission attempts a lease create, activate, renew, or terminate operation, THE System SHALL deny the request with an authorisation error.
4. WHEN a user without the `real_estate.manage_invoices` permission attempts to mark a RentInvoice as paid, THE System SHALL deny the request with an authorisation error.
5. WHEN a user without the `real_estate.manage_maintenance` permission attempts to assign, resolve, or close a MaintenanceRequest, THE System SHALL deny the request with an authorisation error.
6. THE System SHALL scope all data queries (Properties, Leases, RealEstateTenants, RentInvoices, MaintenanceRequests) to the authenticated company `tenant_id`, preventing cross-tenant data access.
7. WHEN a portal user (RealEstateTenant with a linked `user_id`) queries Leases or MaintenanceRequests, THE System SHALL restrict results to records associated with their own `re_tenant_id`.

---

### Requirement 9: Livewire UI Components

**User Story:** As a property manager or tenant, I want a web interface to manage properties, leases, invoices, and maintenance requests, so that I can perform all operations without direct database access.

#### Acceptance Criteria

1. THE System SHALL provide a `PropertyList` Livewire component that allows browsing, filtering by status/type/city, and creating new Properties.
2. THE System SHALL provide a `PropertyDetail` Livewire component that displays a Property's active Leases and open MaintenanceRequests.
3. THE System SHALL provide a `LeaseManager` Livewire component that supports creating, activating, renewing, and terminating Leases.
4. THE System SHALL provide a `TenantList` Livewire component that allows browsing, filtering, and managing RealEstateTenant records.
5. THE System SHALL provide a `RentInvoiceList` Livewire component that displays RentInvoices filterable by status and allows marking invoices as paid.
6. THE System SHALL provide a `MaintenanceBoard` Livewire component that displays MaintenanceRequests in a kanban-style board grouped by status.
7. WHEN a Livewire component action fails validation, THE System SHALL display the validation error to the user without a full page reload.

---

### Requirement 10: Data Integrity and Reference Generation

**User Story:** As a system administrator, I want all records to have unique, human-readable references and consistent data integrity constraints, so that records are traceable and the database remains consistent.

#### Acceptance Criteria

1. THE System SHALL generate references for all entity types using the format `PREFIX-YYYY-NNNNN` where PREFIX is `PROP`, `TEN`, `LSE`, `RNT`, or `MNT`, YYYY is the current year, and NNNNN is a zero-padded sequential number.
2. THE System SHALL enforce database-level unique constraints on all `reference` columns.
3. THE System SHALL enforce a unique constraint on `(lease_id, billing_period)` in `re_rent_invoices` to prevent duplicate monthly invoices.
4. THE System SHALL use database transactions for all operations that involve both a record mutation and a ledger entry (invoice creation, penalty application, mark-paid).
5. WHEN a database transaction fails during invoice generation or penalty application, THE System SHALL roll back all changes for that record and log the error.
6. THE System SHALL apply soft deletes to Properties, RealEstateTenants, and Leases, preserving historical data while excluding deleted records from active queries.
