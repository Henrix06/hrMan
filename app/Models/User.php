<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'tenant_id',
        'employee_id',
        'manager_id',
        'hire_date',
        'status',
        'base_salary',
        'department',
        'department_id',
        'position',
        'phone',

        // Personal
        'date_of_birth', 'gender', 'national_id', 'nationality', 'marital_status',
        // Contact
        'personal_email', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
        // Emergency contact
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
        // Employment terms
        'employment_type', 'contract_start_date', 'contract_end_date', 'probation_end_date',
        'work_location', 'job_grade', 'pay_frequency',
        // Bank & tax
        'bank_name', 'bank_account_number', 'bank_branch', 'tax_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
            'base_salary' => 'decimal:2',
            'date_of_birth' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'probation_end_date' => 'date',
        ];
    }

    /**
     * Get the tenant this user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the manager of the user.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the subordinates of the user.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Get the leave requests for the user.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the leave requests approved by the user.
     */
    public function approvedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    /**
     * Get the leave balances for the user.
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get the payroll details for the user.
     */
    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Get the payslips for the user.
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function hrNotifications(): HasMany
    {
        return $this->hasMany(HrNotification::class);
    }

    public function assetAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'user_id');
    }

    public function activeAssets(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'user_id')->where('status', 'active');
    }

    public function unreadNotificationsCount(): int
    {
        // Cache for 30 seconds per user to avoid hitting DB on every page render
        return Cache::remember(
            "unread_notif_{$this->id}",
            30,
            fn () => $this->hrNotifications()->whereNull('read_at')->count()
        );
    }

    /**
     * Age in whole years, when a date of birth is on file.
     */
    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * The postal address on one line, or null when nothing is recorded.
     */
    public function fullAddress(): ?string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ], fn ($part) => $part !== null && trim((string) $part) !== '');

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Whole years of service since the hire date.
     */
    public function tenureInYears(): ?int
    {
        return $this->hire_date?->diffInYears(now());
    }

    /**
     * Whether the probation window is still open.
     */
    public function isOnProbation(): bool
    {
        if ($this->status === 'probation') {
            return true;
        }

        return $this->probation_end_date !== null && $this->probation_end_date->isFuture();
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('Manager') || $this->hasRole('HR Admin') || $this->hasRole('Super Admin');
    }

    /**
     * Check if user is HR admin or super admin.
     */
    public function isHRAdmin(): bool
    {
        return $this->hasRole('HR Admin') || $this->hasRole('Super Admin');
    }
}
