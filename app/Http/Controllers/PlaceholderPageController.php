<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceholderPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $pages = [
            'employees.index' => ['Employees', 'The employee management screen was previously implemented with Livewire and now needs a server-rendered replacement.'],
            'employees.create' => ['Create Employee', 'This workflow was removed with Livewire and has not yet been rebuilt.'],
            'employees.edit' => ['Edit Employee', 'This editing workflow was removed with Livewire and has not yet been rebuilt.'],
            'leave.apply' => ['Apply For Leave', 'The leave request flow needs a non-Livewire implementation.'],
            'leave.approval' => ['Leave Approvals', 'The approval queue needs a non-Livewire implementation.'],
            'leave.balance' => ['Leave Balance', 'The leave balance page needs a non-Livewire implementation.'],
            'payroll.run' => ['Run Payroll', 'Payroll execution was previously handled in Livewire and is currently disabled.'],
            'payroll.history' => ['Payroll History', 'The payroll history page needs a non-Livewire implementation.'],
            'payslips.index' => ['Payslips', 'The payslip listing page needs a non-Livewire implementation.'],
            'payslips.show' => ['Payslip Details', 'The payslip detail page needs a non-Livewire implementation.'],
            'departments.index' => ['Departments', 'The department management page needs a non-Livewire implementation.'],
            'tasks.index' => ['Tasks', 'The task management page needs a non-Livewire implementation.'],
            'folders.index' => ['Folders', 'The folder manager relied on Livewire interactions and is currently unavailable.'],
            'announcements.index' => ['Announcements', 'The announcements page needs a non-Livewire implementation.'],
            'notifications.index' => ['Notifications', 'The notification center needs a non-Livewire implementation.'],
            'accounts.index' => ['Accounts Dashboard', 'The accounting dashboard needs a non-Livewire implementation.'],
            'accounts.procurement' => ['Procurement', 'The procurement workflow needs a non-Livewire implementation.'],
            'accounts.overtime' => ['Overtime Claims', 'The overtime claims workflow needs a non-Livewire implementation.'],
            'accounts.transport' => ['Transport Claims', 'The transport claims workflow needs a non-Livewire implementation.'],
            'assets.index' => ['Assets', 'The asset management page needs a non-Livewire implementation.'],
            'assets.my' => ['My Assets', 'The personal asset page needs a non-Livewire implementation.'],
            'admin.audit-logs' => ['Audit Logs', 'The audit log viewer needs a non-Livewire implementation.'],
            'real-estate.properties.index' => ['Properties', 'The real estate properties page needs a non-Livewire implementation.'],
            'real-estate.properties.show' => ['Property Details', 'The property detail page needs a non-Livewire implementation.'],
            'real-estate.tenants.index' => ['Tenants', 'The tenants page needs a non-Livewire implementation.'],
            'real-estate.leases.index' => ['Leases', 'The lease manager needs a non-Livewire implementation.'],
            'real-estate.invoices.index' => ['Rent Invoices', 'The invoice management page needs a non-Livewire implementation.'],
            'real-estate.maintenance.index' => ['Maintenance', 'The maintenance board needs a non-Livewire implementation.'],
        ];

        [$title, $description] = $pages[$request->route()->getName()] ?? ['Unavailable Page', 'This page was previously mounted through Livewire and has not yet been rebuilt.'];

        return view('placeholders.feature', [
            'title' => $title,
            'description' => $description,
        ]);
    }
}
