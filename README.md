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
- `/employees/{id}/edit` - Edit employee
- `/leave/apply` - Apply for leave
- `/leave/approval` - Leave approval queue
- `/leave/balance` - Leave balance
- `/payroll/run` - Run payroll
- `/payroll/history` - Payroll history
- `/payslips` - View payslips

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
