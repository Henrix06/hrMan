<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
        return $this->hasMany(\App\Models\AssetAssignment::class, 'user_id');
    }

    public function activeAssets(): HasMany
    {
        return $this->hasMany(\App\Models\AssetAssignment::class, 'user_id')->where('status', 'active');
    }

    public function unreadNotificationsCount(): int
    {
        // Cache for 30 seconds per user to avoid hitting DB on every page render
        return \Illuminate\Support\Facades\Cache::remember(
            "unread_notif_{$this->id}",
            30,
            fn() => $this->hrNotifications()->whereNull('read_at')->count()
        );
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
