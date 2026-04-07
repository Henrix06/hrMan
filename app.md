# HR Management Portal (Laravel + Livewire + Tailwind)

## Project Overview
A custom Human Resources Management (HRM) portal featuring Role-Based Access Control (RBAC), Leave Management, and Payroll processing. 

## Technology Stack
* **Framework:** Laravel (PHP)
* **Reactivity:** Livewire & Alpine.js
* **Styling:** Tailwind CSS
* **Database:** PostgreSQL or MySQL
* **Access Control:** `spatie/laravel-permission` (Industry standard for Laravel RBAC)
* **Starter Kit:** Laravel Breeze or Jetstream (Livewire stack)

---

## Phase 1: Environment Setup & Packages
1. Initialize the Laravel project: `composer create-project laravel/laravel hr-portal`
2. Install a starter kit (Breeze/Jetstream) with the Livewire stack for out-of-the-box authentication.
3. Install dependencies: `npm install` and `npm run dev`.
4. Install Spatie Permissions: `composer require spatie/laravel-permission`.
5. Configure `.env` for your database connections and app URL.

---

## Phase 2: Database & Models (Eloquent)
1. Run Spatie's migration to set up the `roles` and `permissions` tables.
2. Generate Models and Migrations (`php artisan make:model Name -m`) for:
   * `User` (Customize the default Laravel users table to include `manager_id`, `hire_date`, `status`)
   * `LeaveType`
   * `LeaveBalance`
   * `LeaveRequest`
   * `PayrollDetail`
   * `Payslip`
3. Define Eloquent relationships (e.g., a User `hasMany` LeaveRequests, a LeaveRequest `belongsTo` a LeaveType).
4. Create Database Seeders (`php artisan make:seeder`) to populate the initial Super Admin, default HR roles, and standard Leave Types.

---

## Phase 3: Core Logic (Livewire Components)
Instead of building a traditional API, build reactive Livewire components (`php artisan make:livewire ComponentName`):
1. **User Management:** `CreateEmployee`, `EditEmployee`, `EmployeeList` (with real-time search and filtering).
2. **Leave Management:** * `ApplyForLeave`: A reactive form that calculates total days (excluding weekends) on the fly before submission.
   * `LeaveApprovalQueue`: A component for managers to approve/reject requests with a single click.
3. **Payroll Processing:**
   * `RunPayroll`: A component that iterates through active employees, calculates taxes/deductions, and generates the monthly `Payslip` records.

---

## Phase 4: Frontend (Blade & Tailwind)
1. **Layouts:** Customize the default `app.blade.php` layout to include a responsive sidebar navigation built with Tailwind.
2. **Dynamic Navigation:** Use Spatie's Blade directives (`@role('Super Admin|HR Admin')`) to hide/show menu items based on the logged-in user's role.
3. **Dashboards:**
   * **Employee View:** Show remaining leave balance cards and recent payslips.
   * **Admin View:** Show company-wide metrics, pending leave approvals, and quick-action buttons.
4. **Notifications:** Use Livewire's flash messages or a package like SweetAlert2 to confirm actions (e.g., "Leave request submitted!").

---

## Phase 5: Security & Testing
1. **Form Validation:** Utilize Livewire's built-in `$rules` array to strictly validate all incoming data (e.g., ensuring `end_date` is after `start_date`).
2. **Authorization Guards:** Protect backend component methods using Laravel's authorization policies or Spatie's `hasPermissionTo` checks so users cannot force actions via the browser console.
3. **Automated Testing:** Write Pest or PHPUnit tests for the payroll calculation engine to ensure financial accuracy.

---

## Deployment Strategy
1. Provision a server optimized for Laravel (e.g., using Laravel Forge on DigitalOcean or AWS).
2. Set up a managed database instance with automated daily backups.
3. Configure your deployment script to run `php artisan migrate --force`, `php artisan optimize`, and `npm run build`.