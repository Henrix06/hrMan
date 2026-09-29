<?php

use App\Http\Controllers\TaskAttachmentController;
use App\Livewire\Accounts\AccountsDashboard;
use App\Livewire\Accounts\OvertimeClaimList;
use App\Livewire\Accounts\ProcurementList;
use App\Livewire\Accounts\TransportClaimList;
use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Announcements\AnnouncementList;
use App\Livewire\Assets\AssetList;
use App\Livewire\Assets\MyAssets;
use App\Livewire\Dashboard\CompanyDashboard;
use App\Livewire\Departments\DepartmentList;
use App\Livewire\Employees\CreateEmployee;
use App\Livewire\Employees\EditEmployee;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Employees\EmployeeProfile;
use App\Livewire\Folders\FolderManager;
use App\Livewire\Leave\ApplyForLeave;
use App\Livewire\Leave\LeaveApprovalQueue;
use App\Livewire\Leave\LeaveBalance;
use App\Livewire\License\RedeemLicense;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Payroll\PayrollHistory;
use App\Livewire\Payroll\RunPayroll;
use App\Livewire\Payroll\ViewPayslip;
use App\Livewire\Plan\ManagePlan;
use App\Livewire\Pricing\PricingPage;
use App\Livewire\Sales\SalesOrderDocument;
use App\Livewire\Sales\SalesOrderList;
use App\Livewire\Sales\TransportRequestList;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Team\TeamMembers;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('pricing', PricingPage::class)->name('pricing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', CompanyDashboard::class)->name('dashboard');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', EmployeeList::class)->name('index');
        Route::get('create', CreateEmployee::class)->name('create');
        Route::get('{employee}/edit', EditEmployee::class)->name('edit');
        Route::get('{employee}', EmployeeProfile::class)->name('show');
    });

    Route::prefix('leave')->name('leave.')->middleware(['module:leave_management'])->group(function () {
        Route::get('apply', ApplyForLeave::class)->name('apply');
        Route::get('approval', LeaveApprovalQueue::class)->name('approval');
        Route::get('balance', LeaveBalance::class)->name('balance');
    });

    Route::prefix('payroll')->name('payroll.')->middleware(['module:payroll'])->group(function () {
        Route::get('run', RunPayroll::class)->name('run');
        Route::get('history', PayrollHistory::class)->name('history');
    });

    Route::prefix('payslips')->name('payslips.')->middleware(['module:payroll'])->group(function () {
        Route::get('/', PayrollHistory::class)->name('index');
        Route::get('{payslipId}', ViewPayslip::class)->name('show');
    });

    Route::prefix('departments')->name('departments.')->middleware(['module:departments'])->group(function () {
        Route::get('/', DepartmentList::class)->name('index');
    });

    Route::prefix('tasks')->name('tasks.')->middleware(['module:task_management'])->group(function () {
        Route::get('/', TaskList::class)->name('index');
        Route::get('attachments/{attachment}', TaskAttachmentController::class)->name('attachments.download');
    });

    Route::prefix('sales')->name('sales.')->middleware(['module:sales'])->group(function () {
        Route::get('/', SalesOrderList::class)->name('orders');
        Route::get('transport', TransportRequestList::class)->name('transport');
        // Printable document — opened in its own tab and printed (or saved as PDF).
        Route::get('{order}/document', SalesOrderDocument::class)->name('orders.document');
    });

    Route::prefix('folders')->name('folders.')->group(function () {
        Route::get('/', FolderManager::class)->name('index');
    });

    Route::prefix('announcements')->name('announcements.')->middleware(['module:announcements'])->group(function () {
        Route::get('/', AnnouncementList::class)->name('index');
    });

    Route::get('notifications', NotificationCenter::class)->name('notifications.index');

    Route::prefix('accounts')->name('accounts.')->middleware(['module:accounting'])->group(function () {
        Route::get('/', AccountsDashboard::class)->name('index');
        Route::get('procurement', ProcurementList::class)->name('procurement');
        Route::get('overtime', OvertimeClaimList::class)->name('overtime');
        Route::get('transport', TransportClaimList::class)->name('transport');
    });

    Route::prefix('assets')->name('assets.')->middleware(['module:asset_management'])->group(function () {
        Route::get('/', AssetList::class)->name('index');
        Route::get('mine', MyAssets::class)->name('my');
    });

    Route::prefix('admin')->name('admin.')->middleware(['module:audit_logs', 'permission:manage-modules'])->group(function () {
        Route::get('audit-logs', AuditLogViewer::class)->name('audit-logs');
    });

    // Licence activation and plan changes — workspace owners only
    Route::get('license', RedeemLicense::class)
        ->middleware(['role:Super Admin'])
        ->name('license.index');

    Route::get('plan', ManagePlan::class)
        ->middleware(['role:Super Admin'])
        ->name('plan.index');

    // Team invitations — workspace owners only
    Route::get('team', TeamMembers::class)
        ->middleware(['role:Super Admin'])
        ->name('team.index');

});

require __DIR__.'/auth.php';
