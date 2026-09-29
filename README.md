# HR Management Portal

A comprehensive Human Resources Management (HRM) portal built with Laravel, Livewire, and Tailwind CSS.

## Features

- **Role-Based Access Control (RBAC)** - Using Spatie Laravel Permission
  - Super Admin
  - HR Admin
  - Manager
  - Employee

- **Employee Management**
  - Create, edit, and delete employees
  - Track employee information (department, position, manager, etc.)
  - Employee status management (Active, Inactive, Terminated, Probation)

- **Leave Management**
  - Multiple leave types (Annual, Sick, Maternity, Paternity, Bereavement, etc.)
  - Apply for leave with automatic day calculation (excluding weekends)
  - Leave approval workflow
  - Leave balance tracking

- **Payroll Processing**
  - Run monthly payroll
  - Configure allowances, overtime, bonuses, and deductions
  - Automatic tax calculation
  - Generate payslips

- **Task Collaboration**
  - Watchers, labels and checklists
  - File attachments with policy-checked downloads
  - @mentions in comments and a unified activity timeline

- **Sales & Transport**
  - Log sales orders with line items, tax and totals
  - Confirm, fulfil and cancel orders
  - Request transport to move sold items, then approve, dispatch and deliver
  - Print or save any order as an A4 PDF from the browser

- **Dashboard**
  - Employee-specific dashboard
  - Manager view with team statistics
  - Quick access to pending approvals

## Technology Stack

- **Framework:** Laravel 12
- **Reactivity:** Livewire 3 & Volt
- **Styling:** Tailwind CSS
- **Database:** SQLite (default) / MySQL / PostgreSQL
- **Access Control:** Spatie Laravel Permission

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM

### Setup Instructions

1. **Navigate to the project directory:**
   ```bash
   cd hr-portal
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node dependencies:**
   ```bash
   npm install
   ```

4. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database** (update `.env` file):
   ```
   DB_CONNECTION=sqlite
   # Or for MySQL:
   # DB_CONNECTION=mysql
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=hr_portal
   # DB_USERNAME=root
   # DB_PASSWORD=
   ```

6. **Run migrations and seeders:**
   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Build assets:**
   ```bash
   npm run build
   ```

8. **Start the development server:**
   ```bash
   php artisan serve
   ```

9. **Access the application:**
   - Open your browser and navigate to `http://localhost:8000`

## Default Login Credentials

After running the seeders, you can log in with these credentials:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@hrportal.com | password123 |
| HR Admin | hradmin@hrportal.com | password123 |
| Manager | manager@hrportal.com | password123 |
| Employee | employee@hrportal.com | password123 |
| Employee | employee2@hrportal.com | password123 |

## Licence Codes

A company can activate or upgrade its workspace by redeeming a licence code. A code carries a **plan**, a **duration** (or a perpetual licence), an optional **validity window**, and a **redemption limit**.

Sign in as a **Super Admin** and open **Licence** in the sidebar (`/license`) to enter a code. Codes are case-insensitive, so `UMSIZI-DEMO-PRO-2026`, `umsizi demo pro 2026` and `UMSIZIDEMOPRO2026` all work.

### Demo codes (created by the seeder)

| Code | Plan | Duration | Max redemptions |
|------|------|----------|-----------------|
| `UMSIZI-DEMO-PRO-2026` | Professional | 365 days | 25 |
| `UMSIZI-DEMO-TEAM-2026` | Starter | 180 days | 25 |

### Generating codes

```bash
# One Professional code, valid for a year
php artisan license:generate --plan=professional --days=365

# Five Starter codes, each usable by 3 companies, codes expire in 30 days
php artisan license:generate --plan=starter --days=90 --count=5 --max-redemptions=3 --expires-in=30

# A perpetual licence with unlimited redemptions
php artisan license:generate --plan=enterprise --days=0 --max-redemptions=0

# Review codes and how often they have been redeemed
php artisan license:list
```

Redeeming a code sets the company's plan, enables that plan's modules, clears the free trial, and sets the access expiry. Each company can redeem a given code only once, and a second code extends the existing expiry rather than replacing it. Redemptions are written to the audit log.

## Changing Plans In-App

A **Super Admin** can change the company's plan without re-signing up from **Plan & Modules** in the sidebar (`/plan`). The page shows every plan next to the current one, highlighting which modules a switch would turn on or off.

Switching is immediate — there is no checkout step:

- **Upgrading** enables the new plan's modules straight away.
- A switch keeps any active entitlement window, so paid-for time (from a licence code) or a running trial is never lost.
- If the company has no active entitlement, a fresh `pricing.trial_days` (default 14) trial starts automatically.
- **Downgrading to Free** never expires and turns off the paid modules. Existing data is kept and reappears if the company switches back.
- **Enterprise** is priced per customer, so it can't be self-served — the card links to the pricing page instead.

If the company's headcount exceeds the target plan's `employee_limit`, the confirmation step warns about it. Limits are advisory today; they are not yet enforced when adding employees.

Plan changes are recorded in the audit log. Internally, [PlanService](app/Services/PlanService.php) owns plan changes, module syncing and entitlement windows; licence redemption delegates to it.

## Sales & Transport

The **Sales** module (Professional and Enterprise plans) covers the two halves of getting a sale out of the door: logging it, and getting the goods to the customer.

**Orders** (`/sales`) — any team member with *create sales order* logs what they sold: customer details, the pickup and delivery addresses, a date, an optional tax rate and as many line items as they need. Line totals, subtotal, tax and total are calculated for them. Orders are saved as a **draft** (still editable) or logged straight as **confirmed**. Managers and HR Admin can then confirm, fulfil or cancel them, and the person who logged the order is notified each time.

**Printable document** (`/sales/{order}/document`) — every order prints. The print icon on an order opens a clean A4 document in its own tab: company and reference in the header, customer and delivery blocks, the itemised table, subtotal/tax/total, any transport already arranged, notes and signature lines for whoever receives and delivers the goods. A **Print / Save as PDF** button hands it to the browser's print dialog, so the same page covers paper and PDF without a PDF service. Drafts carry a visible "not confirmed yet" warning so a proforma can't be mistaken for a final order. The document is styled by [layouts/print.blade.php](resources/views/layouts/print.blade.php), which ships its own CSS rather than relying on the app's bundle, and it enforces the same rule as the orders page — you can only print an order you logged yourself, or one belonging to a company you can manage.

**Transport Requests** (`/sales/transport`) — once an order is confirmed it can be moved. Whoever sold it opens a request (a button on the order deep links into a prefilled form) choosing what is being moved, who to contact at each end and a preferred date. Everyone who holds *manage transport* is told there is something to move, and runs the request through **pending → approved → in transit → delivered**, assigning a driver and vehicle at approval. The person who requested it is notified at each step, and the driver is told when the run is dispatched.

Rules enforced along the way:

- Only **draft** orders can be edited; a confirmed order must be cancelled and re-logged instead.
- Transport can only be arranged for a **confirmed** order, and only by the person who logged it (or someone who can manage sales).
- An order can have **one open transport request** at a time — cancel or deliver it before raising another.
- Employees only ever see their own orders and transport requests; managers and HR Admin see the whole company. Cross-company access returns 404. A driver can see (but not run) the runs assigned to them.
- Approving a transport request requires a driver.

Order and transport references are generated per company and year (`SO-2026-00001`, `TR-2026-00001`), and every state change is written to the audit log. The rules live in [SalesOrderService](app/Services/SalesOrderService.php) and [TransportRequestService](app/Services/TransportRequestService.php), with defaults in [config/sales.php](config/sales.php).

## Inviting Teammates

A **Super Admin** adds people to the workspace from **Team** in the sidebar (`/team`) by entering their email and choosing a role. Nobody has to invent a password for someone else — the invitee sets their own when they accept.

What happens:

1. The inviter enters an email and role; the app creates a pending invitation and emails a one-time link.
2. The link opens `/invitation/{token}`, a guest page showing the workspace name and the role being granted.
3. The invitee sets their name and password. Their account is created in the inviter's workspace, verified, given the chosen role, and given leave balances from the active leave types. They're signed in and land on the dashboard.

Rules that are enforced:

- Only **Super Admins** can open the Team page or issue invitations.
- The invited email can't already belong to an account, and only one invitation per email can be pending at a time.
- Invitation tokens are never stored in plain text — the database keeps a SHA-256 hash, so the emailed link is the only copy.
- Links expire after `team.invitation_expiry_days` (default 7). Expired or mislaid links can be renewed with **Renew link**, or cancelled entirely from the pending list.

This complements rather than replaces the **Create Employee** screen: that form captures HR detail (department, position, manager, salary, employee ID), while invitations grant sign-in access. Super Admins now see a prompt on the employee form pointing them to Team when access is what they actually need.

## Employee HR Records

Each employee record captures far more than a login. Alongside the core fields (name, work email, employee ID, department, position, manager, hire date, status, salary) the profile holds five further groups, all editable from the create and edit forms:

- **Personal** — date of birth, gender, national ID / passport, nationality, marital status
- **Contact** — personal email, street address, city, province/state, postal code, country
- **Emergency contact** — name, relationship, phone
- **Employment terms** — employment type, contract start/end, probation end, work location, job grade, pay frequency
- **Payment & tax** — bank name, branch, account number, tax number

Every employee has a read-only profile at `/employees/{id}`, reachable by clicking a name in the employee list. It groups the record into sections and derives values such as age, tenure, full postal address and whether the probation window is still open.

Field definitions live in one place, [HrProfileFields](app/Support/Employees/HrProfileFields.php), so the form, the validation rules, the profile page and the persistence actions can't drift apart. Table-stakes robustness is covered too: cross-company access returns 404, employees may view their own profile but not a colleague's, blank inputs are stored as `null` rather than empty strings, and enum values and date ordering (a contract can't end before it starts) are validated.

## Task Collaboration

Opening a task reveals a collaboration drawer layered on top of the original create/edit flow:

- **Watchers** — anyone who can see a task can follow it with one click. Watchers are notified about new comments and status changes, and a task creator or HR Admin can add or remove other people as watchers.
- **Labels** — free-form labels (up to 10 per task, from `config/tasks.php`) added in bulk by separating them with commas, removed with one click, and filterable from the task list. Duplicates are rejected case-insensitively.
- **Checklists** — ordered sub-items with a progress bar. Anyone who can see the task can tick, untick, add and remove items, so an assignee can work the list without edit rights.
- **Attachments** — files up to 10 MB from an allow-list of extensions, stored per company and per task on the configured disk. Downloads run through [TaskAttachmentController](app/Http/Controllers/TaskAttachmentController.php), which re-checks the task policy, so a file is never reachable by guessing its path. The uploader, the task creator and HR Admin can delete a file.
- **@mentions** — mention a teammate in a comment by name (`@jane.doe`, `@"Jane Doe"`) or by their email local part. Mentioned teammates get a dedicated notification; ambiguous handles such as a first name shared by two colleagues are ignored rather than notifying the wrong person.
- **Activity timeline** — comments and system events (creation, status changes, reassignment, label/checklist/attachment/watcher changes and mentions) are interleaved newest-first in a single feed, so the history of a task reads as one story.

The collaboration rules live in [TaskCollaborationService](app/Services/TaskCollaborationService.php) and mention parsing in [MentionParser](app/Support/Tasks/MentionParser.php), keeping the Livewire component focused on presentation.

## User Roles and Permissions

### Super Admin
- Full system access
- Manage roles and permissions
- View audit logs
- Delete records

### HR Admin
- Manage all employees
- Run payroll
- Approve/reject leave requests
- Generate reports
- Edit payroll

### Manager
- View team members
- Approve/reject leave requests (for subordinates only)
- Create and edit employees

### Employee
- View own profile
- Apply for leave
- View own payslips
- Edit own profile

## Project Structure

```
hr-portal/
├── app/
│   ├── Livewire/          # Livewire components
│   ├── Models/            # Eloquent models
│   └── ...
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/           # Database seeders
│   └── ...
├── resources/
│   └── views/
│       ├── livewire/      # Livewire component views
│       ├── layouts/       # Blade layouts
│       └── ...
├── routes/
│   └── web.php            # Web routes
└── ...
```

## Available Routes

- `/dashboard` - Main dashboard
- `/employees` - Employee list
- `/employees/create` - Create employee
- `/employees/{id}` - Employee HR profile
- `/employees/{id}/edit` - Edit employee
- `/leave/apply` - Apply for leave
- `/leave/approval` - Leave approval queue
- `/leave/balance` - Leave balance
- `/payroll/run` - Run payroll
- `/payroll/history` - Payroll history
- `/payslips` - View payslips
- `/license` - Redeem a licence code (Super Admin)
- `/plan` - Change plan and modules in-app (Super Admin)
- `/team` - Invite teammates and manage members (Super Admin)
- `/invitation/{token}` - Accept a teammate invitation (guest)
- `/tasks` - Task board with the collaboration drawer (labels, checklist, attachments, watchers, activity)
- `/tasks/attachments/{id}` - Download a task attachment (authorised by the task policy)
- `/sales` - Sales orders (log, confirm, fulfil, cancel)
- `/sales/{id}/document` - Printable sales order document (print or save as PDF)
- `/sales/transport` - Transport requests for confirmed orders

## Security Features

- Form validation on all inputs
- Authorization guards using Spatie permissions
- CSRF protection
- Password hashing
- Role-based access control

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
composer format
```

### Watch for changes (development)
```bash
npm run dev
```

## Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Run `composer install --optimize-autoloader --no-dev`
3. Run `npm run build`
4. Run `php artisan config:cache`
5. Run `php artisan route:cache`
6. Run `php artisan view:cache`
7. Run `php artisan migrate --force`

## License

This project is open-sourced software.

## Support

For issues and questions, please create an issue in the repository.
