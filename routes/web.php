<?php

use App\Http\Controllers\Admin\Company\ActivityLogController;
use App\Http\Controllers\Admin\Company\ClientController;
use App\Http\Controllers\Admin\Company\HolidayController;
use App\Http\Controllers\Admin\Company\RoleController;
use App\Http\Controllers\Admin\Company\ScreenCaptureController;
use App\Http\Controllers\Admin\Company\SettingsController as CompanySettingsController;
use App\Http\Controllers\Admin\Crm\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\Crm\LeadImportController as AdminLeadImportController;
use App\Http\Controllers\Admin\Crm\OverviewController as CrmOverviewController;
use App\Http\Controllers\Admin\Crm\ReportController as CrmReportController;
use App\Http\Controllers\Admin\Crm\SettingsController as CrmSettingsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Finance\ChartOfAccountController;
use App\Http\Controllers\Admin\Finance\ExpenseCategoryController;
use App\Http\Controllers\Admin\Finance\ExpenseController;
use App\Http\Controllers\Admin\Finance\InvoiceController;
use App\Http\Controllers\Admin\Finance\JournalEntryController;
use App\Http\Controllers\Admin\Finance\LedgerController;
use App\Http\Controllers\Admin\Finance\OverviewController as FinanceOverviewController;
use App\Http\Controllers\Admin\Finance\PaymentController;
use App\Http\Controllers\Admin\Finance\ReportController as FinanceReportController;
use App\Http\Controllers\Admin\Finance\SettingsController as FinanceSettingsController;
use App\Http\Controllers\Admin\Finance\VendorController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\Hr\AttendanceController as HrAttendanceController;
use App\Http\Controllers\Admin\Hr\AttendanceImportController;
use App\Http\Controllers\Admin\Hr\DepartmentController;
use App\Http\Controllers\Admin\Hr\DesignationController;
use App\Http\Controllers\Admin\Hr\EmployeeController as HrEmployeeController;
use App\Http\Controllers\Admin\Hr\EmployeeImportController;
use App\Http\Controllers\Admin\Hr\EmployeePayrollController;
use App\Http\Controllers\Admin\Hr\LeaveController as HrLeaveController;
use App\Http\Controllers\Admin\Hr\LeaveTypeController;
use App\Http\Controllers\Admin\Hr\PayrollComponentController;
use App\Http\Controllers\Admin\Hr\PayrollController;
use App\Http\Controllers\Admin\Hr\ReportController as HrReportController;
use App\Http\Controllers\Admin\Hr\SettingsController as HrSettingsController;
use App\Http\Controllers\Admin\Hr\ShiftController;
use App\Http\Controllers\Admin\Projects\MilestoneController as ProjectMilestoneController;
use App\Http\Controllers\Admin\Projects\ProjectArchiveController;
use App\Http\Controllers\Admin\Projects\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\Projects\TaskController as ProjectTaskController;
use App\Http\Controllers\Admin\Projects\TimesheetController as ProjectTimesheetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeadController as EmployeeLeadController;
use App\Http\Controllers\Employee\LeadImportController as EmployeeLeadImportController;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Employee\ProjectController as EmployeeProjectController;
use App\Http\Controllers\Employee\TaskController as EmployeeTaskController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest — single login screen for every role
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'show'])->name('login');
    Route::get('/login', [AuthController::class, 'show'])->name('login.show');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    /*
    |----------------------------------------------------------------------
    | Super Admin — platform console (sidebar layout)
    |----------------------------------------------------------------------
    */
    Route::prefix('superadmin')->name('superadmin.')->middleware('role:superadmin')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/companies/{tenant}/enter', [CompanyController::class, 'enter'])->name('companies.enter');
        Route::post('/exit-impersonation', [CompanyController::class, 'exitImpersonation'])->name('exit-impersonation');
    });

    /*
    |----------------------------------------------------------------------
    | Admin Portal — owner / admin / manager-HOD (two-tier header layout)
    | Superadmin also lands here while impersonating a tenant.
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware(['role:owner,admin,manager,superadmin', 'module.permission'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        // Accounts & Finance
        Route::prefix('finance')->name('finance.')->middleware('role:owner,admin,superadmin')->group(function () {
            Route::get('/overview', [FinanceOverviewController::class, 'index'])->name('overview.index');

            Route::get('/setup', [FinanceSettingsController::class, 'edit'])->name('settings');
            Route::put('/setup', [FinanceSettingsController::class, 'update'])->name('settings.update');

            Route::post('/coa/seed-defaults', [ChartOfAccountController::class, 'seedDefaults'])->name('coa.seed-defaults');
            Route::resource('coa', ChartOfAccountController::class)->except(['show']);

            Route::resource('invoices', InvoiceController::class)->except(['edit', 'update', 'destroy']);
            Route::post('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markSent'])->name('invoices.mark-sent');
            Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/export/{format}', [PaymentController::class, 'exportList'])->name('payments.export');
            Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
            Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

            Route::resource('vendors', VendorController::class)->except(['show']);
            Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);

            Route::get('/expenses/export/{format}', [ExpenseController::class, 'exportList'])->name('expenses.export');
            Route::resource('expenses', ExpenseController::class);
            Route::post('/expenses/{expense}/submit', [ExpenseController::class, 'submit'])->name('expenses.submit');
            Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
            Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
            Route::post('/expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');
            Route::post('/expenses/{expense}/cancel', [ExpenseController::class, 'cancel'])->name('expenses.cancel');
            Route::post('/expenses/{expense}/duplicate', [ExpenseController::class, 'duplicate'])->name('expenses.duplicate');
            Route::get('/expenses/{expense}/attachments/{attachment}', [ExpenseController::class, 'downloadAttachment'])->name('expenses.attachments.download');

            Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
            Route::get('/ledger/accounts/{coa}', [LedgerController::class, 'show'])->name('ledger.accounts.show');
            Route::get('/ledger/accounts/{coa}/export/{format}', [LedgerController::class, 'exportAccount'])->name('ledger.accounts.export');
            Route::get('/ledger/entries', [JournalEntryController::class, 'index'])->name('ledger.entries.index');
            Route::get('/ledger/entries/create', [JournalEntryController::class, 'create'])->name('ledger.entries.create');
            Route::post('/ledger/entries', [JournalEntryController::class, 'store'])->name('ledger.entries.store');
            Route::get('/ledger/entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('ledger.entries.show');

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [FinanceReportController::class, 'index'])->name('index');

                Route::get('/profit-loss', [FinanceReportController::class, 'profitLoss'])->name('profit-loss');
                Route::get('/profit-loss/export/{format}', [FinanceReportController::class, 'profitLossExport'])->name('profit-loss.export');

                Route::get('/expenses', [FinanceReportController::class, 'expenses'])->name('expenses');
                Route::get('/expenses/export/{format}', [FinanceReportController::class, 'expensesExport'])->name('expenses.export');

                Route::get('/revenue', [FinanceReportController::class, 'revenue'])->name('revenue');
                Route::get('/revenue/export/{format}', [FinanceReportController::class, 'revenueExport'])->name('revenue.export');

                Route::get('/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('trial-balance');
                Route::get('/trial-balance/export/{format}', [FinanceReportController::class, 'trialBalanceExport'])->name('trial-balance.export');
            });
        });

        // Company
        Route::prefix('company')->name('company.')->middleware('role:owner,admin,manager,superadmin')->group(function () {
            Route::get('/settings', [CompanySettingsController::class, 'edit'])->name('settings');
            Route::put('/settings', [CompanySettingsController::class, 'update'])->name('settings.update');
            Route::resource('holidays', HolidayController::class)->except(['show']);

            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');

            // Defining who can access what — and reading the audit trail —
            // is itself sensitive, narrower than the rest of Company, which
            // managers can otherwise use. Screenshots of an employee's
            // screen belong in this same narrower group.
            Route::middleware('role:owner,admin,superadmin')->group(function () {
                Route::resource('roles', RoleController::class)->except(['show']);
                Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

                Route::get('/user-working', [ScreenCaptureController::class, 'index'])->name('user-working.index');
                Route::get('/user-working/{screenCapture}/view', [ScreenCaptureController::class, 'view'])->name('user-working.view');
                Route::delete('/user-working/{screenCapture}', [ScreenCaptureController::class, 'destroy'])->name('user-working.destroy');
                Route::post('/user-working/employees/{user}/revoke-agent', [ScreenCaptureController::class, 'revokeAgent'])->name('user-working.revoke-agent');
            });
        });

        // Leads / CRM
        Route::prefix('crm')->name('crm.')->middleware('role:owner,admin,superadmin')->group(function () {
            Route::get('/overview', [CrmOverviewController::class, 'index'])->name('overview.index');

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/leads', [CrmReportController::class, 'index'])->name('index');
                Route::get('/leads/export/{format}', [CrmReportController::class, 'leadsExport'])->name('export');
            });

            Route::get('/setup', [CrmSettingsController::class, 'edit'])->name('settings');
            Route::put('/setup', [CrmSettingsController::class, 'update'])->name('settings.update');

            Route::get('/followups', [AdminLeadController::class, 'followupsIndex'])->name('followups.index');
            Route::get('/approvals', [AdminLeadController::class, 'approvals'])->name('leads.approvals');

            // Bulk import — these two GETs must be registered before the
            // "leads" resource below, or "/leads/import" would be swallowed
            // by the resource's "/leads/{lead}" show route.
            Route::get('/leads/import/template', [AdminLeadImportController::class, 'template'])->name('leads.import.template');
            Route::get('/leads/import', [AdminLeadImportController::class, 'create'])->name('leads.import.create');
            Route::post('/leads/import', [AdminLeadImportController::class, 'store'])->name('leads.import.store');

            Route::post('/leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.status');
            Route::post('/leads/{lead}/activity', [AdminLeadController::class, 'addActivity'])->name('leads.activity');
            Route::post('/leads/{lead}/followups', [AdminLeadController::class, 'storeFollowup'])->name('leads.followups.store');
            Route::post('/leads/{lead}/followups/{followup}/complete', [AdminLeadController::class, 'completeFollowup'])->name('leads.followups.complete');
            Route::post('/leads/{lead}/approve', [AdminLeadController::class, 'approve'])->name('leads.approve');
            Route::post('/leads/{lead}/reject', [AdminLeadController::class, 'reject'])->name('leads.reject');
            Route::resource('leads', AdminLeadController::class);

            Route::get('/imports', [AdminLeadImportController::class, 'index'])->name('imports.index');
            Route::get('/imports/{batch}', [AdminLeadImportController::class, 'show'])->name('imports.show');
            Route::post('/imports/{batch}/approve', [AdminLeadImportController::class, 'approve'])->name('imports.approve');
            Route::post('/imports/{batch}/reject', [AdminLeadImportController::class, 'reject'])->name('imports.reject');
        });

        // HR
        Route::prefix('hr')->name('hr.')->middleware('role:owner,admin,manager,superadmin')->group(function () {
            Route::get('/employees', [HrEmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [HrEmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [HrEmployeeController::class, 'store'])->name('employees.store');

            // Bulk import — static paths must come before "/employees/{employee}"
            // below, or they'd be swallowed by that wildcard.
            Route::get('/employees/import/template', [EmployeeImportController::class, 'template'])->name('employees.import.template');
            Route::get('/employees/import', [EmployeeImportController::class, 'create'])->name('employees.import.create');
            Route::post('/employees/import', [EmployeeImportController::class, 'store'])->name('employees.import.store');

            Route::get('/employees/{employee}', [HrEmployeeController::class, 'show'])->name('employees.show');
            Route::get('/employees/{employee}/edit', [HrEmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [HrEmployeeController::class, 'update'])->name('employees.update');
            Route::post('/employees/{employee}/reset-password', [HrEmployeeController::class, 'resetPassword'])->name('employees.reset-password');

            // Salary data used to be owner/admin-only outright. It now follows
            // the same model as the rest of HR — base access matches the
            // outer hr. group (managers included) — with the custom-role
            // system as the real restriction: to keep payroll away from a
            // manager, give them a role that doesn't grant the Payroll
            // module, the same way Attendance/Shifts/Leave are already
            // scoped per-manager today.
            Route::get('/employees/{employee}/payroll', [EmployeePayrollController::class, 'edit'])->name('employees.payroll.edit');
            Route::put('/employees/{employee}/payroll', [EmployeePayrollController::class, 'update'])->name('employees.payroll.update');
            Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');

            // Payroll *component* configuration (allowances, deductions,
            // etc.) stays owner/admin-only — that's Setup, not Payroll.
            Route::middleware('role:owner,admin,superadmin')->group(function () {
                Route::resource('payroll-components', PayrollComponentController::class)
                    ->except(['show'])
                    ->parameters(['payroll-components' => 'payrollComponent']);
            });

            Route::get('/attendance', [HrAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/attendance/import', [AttendanceImportController::class, 'index'])->name('attendance.import.index');
            Route::get('/attendance/import/template', [AttendanceImportController::class, 'template'])->name('attendance.import.template');
            Route::post('/attendance/import', [AttendanceImportController::class, 'importStore'])->name('attendance.import.store');
            Route::post('/attendance/manual', [AttendanceImportController::class, 'manualStore'])->name('attendance.manual.store');
            Route::get('/attendance/{user}', [HrAttendanceController::class, 'show'])->name('attendance.show');
            Route::post('/attendance/{user}/shift', [HrAttendanceController::class, 'assignShift'])->name('attendance.assign-shift');

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [HrReportController::class, 'index'])->name('index');

                Route::get('/attendance', [HrReportController::class, 'attendance'])->name('attendance');
                Route::get('/attendance/export/{format}', [HrReportController::class, 'attendanceExport'])->name('attendance.export');

                Route::get('/employees', [HrReportController::class, 'employees'])->name('employees');
                Route::get('/employees/export/{format}', [HrReportController::class, 'employeesExport'])->name('employees.export');

                Route::get('/leave', [HrReportController::class, 'leave'])->name('leave');
                Route::get('/leave/export/{format}', [HrReportController::class, 'leaveExport'])->name('leave.export');

                Route::get('/performance', [HrReportController::class, 'performance'])->name('performance');
                Route::get('/performance/export/{format}', [HrReportController::class, 'performanceExport'])->name('performance.export');

                // Salary data — narrower than the rest of Reports.
                Route::middleware('role:owner,admin,superadmin')->group(function () {
                    Route::get('/payroll', [HrReportController::class, 'payroll'])->name('payroll');
                    Route::get('/payroll/export/{format}', [HrReportController::class, 'payrollExport'])->name('payroll.export');
                });
            });

            Route::get('/leaves', [HrLeaveController::class, 'index'])->name('leaves.index');
            Route::get('/leaves/{user}', [HrLeaveController::class, 'show'])->name('leaves.show');
            Route::post('/leaves/{leave}/approve', [HrLeaveController::class, 'approve'])->name('leaves.approve');
            Route::post('/leaves/{leave}/reject', [HrLeaveController::class, 'reject'])->name('leaves.reject');

            Route::get('/setup', [HrSettingsController::class, 'edit'])->name('settings');
            Route::put('/setup', [HrSettingsController::class, 'update'])->name('settings.update');
            Route::post('/setup/payroll-lock', [HrSettingsController::class, 'lockPayroll'])->name('settings.payroll-lock');
            Route::delete('/setup/payroll-lock', [HrSettingsController::class, 'unlockPayroll'])->name('settings.payroll-unlock');
            Route::resource('shifts', ShiftController::class)->except(['show']);
            Route::resource('leave-types', LeaveTypeController::class)
                ->except(['show'])
                ->parameters(['leave-types' => 'leaveType']);
            Route::resource('departments', DepartmentController::class)->except(['show']);
            Route::resource('designations', DesignationController::class)->except(['show']);
        });

        // Projects
        Route::prefix('projects')->name('projects.')->middleware('role:owner,admin,manager,superadmin')->group(function () {
            Route::get('/', [AdminProjectController::class, 'index'])->name('index');
            Route::get('/create', [AdminProjectController::class, 'create'])->name('create');
            Route::post('/', [AdminProjectController::class, 'store'])->name('store');
            Route::get('/boards', [AdminProjectController::class, 'boards'])->name('boards.index');

            Route::get('/milestones', [ProjectMilestoneController::class, 'index'])->name('milestones.index');
            Route::post('/milestones', [ProjectMilestoneController::class, 'store'])->name('milestones.store');
            Route::post('/milestones/{milestone}/complete', [ProjectMilestoneController::class, 'complete'])->name('milestones.complete');
            Route::delete('/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy'])->name('milestones.destroy');

            Route::get('/timesheets', [ProjectTimesheetController::class, 'index'])->name('timesheets.index');
            Route::post('/timesheets', [ProjectTimesheetController::class, 'store'])->name('timesheets.store');
            Route::delete('/timesheets/{timesheet}', [ProjectTimesheetController::class, 'destroy'])->name('timesheets.destroy');

            Route::get('/{project}', [AdminProjectController::class, 'show'])->name('show');
            Route::get('/{project}/edit', [AdminProjectController::class, 'edit'])->name('edit');
            Route::put('/{project}', [AdminProjectController::class, 'update'])->name('update');

            Route::get('/{project}/tasks', [ProjectTaskController::class, 'index'])->name('tasks.index');
            Route::post('/{project}/tasks', [ProjectTaskController::class, 'store'])->name('tasks.store');
            Route::put('/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])->name('tasks.update');
            Route::delete('/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');

            Route::post('/{project}/archive', [ProjectArchiveController::class, 'archive'])->name('archive');
            Route::post('/{project}/restore', [ProjectArchiveController::class, 'restore'])->name('restore');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Employee Portal (sidebar layout)
    |----------------------------------------------------------------------
    */
    Route::prefix('employee')->name('employee.')->middleware(['role:employee', 'module.permission'])->group(function () {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('dashboard');

        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');

        Route::get('/leaves', [EmployeeLeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [EmployeeLeaveController::class, 'store'])->name('leaves.store');

        // Bulk import — registered before the "leads" resource below, same
        // reason as the admin side: avoid "/leads/{lead}" swallowing these.
        Route::get('/leads/import/template', [EmployeeLeadImportController::class, 'template'])->name('leads.import.template');
        Route::get('/leads/import', [EmployeeLeadImportController::class, 'create'])->name('leads.import.create');
        Route::post('/leads/import', [EmployeeLeadImportController::class, 'store'])->name('leads.import.store');
        Route::get('/leads/imports', [EmployeeLeadImportController::class, 'index'])->name('leads.imports.index');
        Route::get('/leads/imports/{batch}', [EmployeeLeadImportController::class, 'show'])->name('leads.imports.show');

        Route::post('/leads/{lead}/status', [EmployeeLeadController::class, 'updateStatus'])->name('leads.status');
        Route::post('/leads/{lead}/activity', [EmployeeLeadController::class, 'addActivity'])->name('leads.activity');
        Route::post('/leads/{lead}/followups', [EmployeeLeadController::class, 'storeFollowup'])->name('leads.followups.store');
        Route::post('/leads/{lead}/followups/{followup}/complete', [EmployeeLeadController::class, 'completeFollowup'])->name('leads.followups.complete');
        Route::resource('leads', EmployeeLeadController::class)->except(['destroy']);

        Route::get('/projects', [EmployeeProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [EmployeeProjectController::class, 'show'])->name('projects.show');

        Route::get('/tasks', [EmployeeTaskController::class, 'index'])->name('tasks.index');
        Route::put('/tasks/{task}', [EmployeeTaskController::class, 'updateStatus'])->name('tasks.update-status');
    });
});