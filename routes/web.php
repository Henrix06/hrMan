<?php

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
use App\Livewire\Leave\ApplyForLeave;
use App\Livewire\Leave\LeaveApprovalQueue;
use App\Livewire\Leave\LeaveBalance;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Payroll\PayrollHistory;
use App\Livewire\Payroll\RunPayroll;
use App\Livewire\Payroll\ViewPayslip;
use App\Livewire\Folders\FolderManager;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Pricing\PricingPage;
use App\Livewire\RealEstate\LeaseManager;
use App\Livewire\RealEstate\MaintenanceBoard;
use App\Livewire\RealEstate\PropertyDetail;
use App\Livewire\RealEstate\PropertyList;
use App\Livewire\RealEstate\RentInvoiceList;
use App\Livewire\RealEstate\TenantList;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('pricing', PricingPage::class)->name('pricing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', CompanyDashboard::class)->name('dashboard');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', EmployeeList::class)->name('index');
        Route::get('create', CreateEmployee::class)->name('create');
        Route::get('{employee}/edit', EditEmployee::class)->name('edit');
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

    Route::prefix('real-estate')->name('real-estate.')->middleware(['module:real_estate'])->group(function () {
        Route::get('properties', PropertyList::class)->name('properties.index');
        Route::get('properties/{propertyId}', PropertyDetail::class)->name('properties.show');
        Route::get('tenants', TenantList::class)->name('tenants.index');
        Route::get('leases', LeaseManager::class)->name('leases.index');
        Route::get('invoices', RentInvoiceList::class)->name('invoices.index');
        Route::get('maintenance', MaintenanceBoard::class)->name('maintenance.index');
    });
});

require __DIR__.'/auth.php';
