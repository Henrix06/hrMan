<?php

use App\Livewire\Accounts\AccountsDashboard;
use App\Livewire\Accounts\OvertimeClaimList;
use App\Livewire\Accounts\ProcurementList;
use App\Livewire\Accounts\TransportClaimList;
use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Announcements\AnnouncementList;
use App\Livewire\Assets\AssetList;
use App\Livewire\Assets\MyAssets;
use App\Livewire\Dashboard\EmployeeDashboard;
use App\Livewire\Departments\DepartmentList;
use App\Livewire\Employees\CreateEmployee;
use App\Livewire\Employees\EditEmployee;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Leave\ApplyForLeave;
use App\Livewire\Leave\LeaveApprovalQueue;
use App\Livewire\Leave\LeaveBalance;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Payroll\PayrollHistory;
use App\Livewire\Payroll\RunPayroll;
use App\Livewire\Payroll\ViewPayslip;
use App\Livewire\Tasks\TaskList;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', EmployeeDashboard::class)->name('dashboard');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', EmployeeList::class)->name('index');
        Route::get('create', CreateEmployee::class)->name('create');
        Route::get('{employee}/edit', EditEmployee::class)->name('edit');
    });

    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('apply', ApplyForLeave::class)->name('apply');
        Route::get('approval', LeaveApprovalQueue::class)->name('approval');
        Route::get('balance', LeaveBalance::class)->name('balance');
    });

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('run', RunPayroll::class)->name('run');
        Route::get('history', PayrollHistory::class)->name('history');
    });

    Route::prefix('payslips')->name('payslips.')->group(function () {
        Route::get('/', PayrollHistory::class)->name('index');
        Route::get('{payslipId}', ViewPayslip::class)->name('show');
    });

    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', DepartmentList::class)->name('index');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', TaskList::class)->name('index');
    });

    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', AnnouncementList::class)->name('index');
    });

    Route::get('notifications', NotificationCenter::class)->name('notifications.index');

    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', AccountsDashboard::class)->name('index');
        Route::get('procurement', ProcurementList::class)->name('procurement');
        Route::get('overtime', OvertimeClaimList::class)->name('overtime');
        Route::get('transport', TransportClaimList::class)->name('transport');
    });

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/', AssetList::class)->name('index');
        Route::get('mine', MyAssets::class)->name('my');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('audit-logs', AuditLogViewer::class)->name('audit-logs');
    });
});

require __DIR__.'/auth.php';
