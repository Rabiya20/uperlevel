<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::query()->delete();

        $this->seedSuperAdmin();
        $this->seedAdminPortal();
        $this->seedEmployeePortal();
    }

    protected function make(string $portal, string $key, string $name, string $roles, ?string $icon = null, ?string $route = null, int $order = 0, ?int $parentId = null): Module
    {
        return Module::create([
            'portal' => $portal,
            'parent_id' => $parentId,
            'key' => $key,
            'name' => $name,
            'icon' => $icon,
            'route_name' => $route,
            'roles' => $roles,
            'sort_order' => $order,
        ]);
    }

    protected function seedSuperAdmin(): void
    {
        $r = 'superadmin';
        $this->make($r, 'dashboard', 'Dashboard', $r, 'grid', 'superadmin.dashboard', 1);
        $this->make($r, 'tenants', 'Tenants', $r, 'building', 'superadmin.companies.index', 2);
        $this->make($r, 'plans', 'Subscription Plans', $r, 'file-invoice', null, 3);
        $this->make($r, 'module-mgmt', 'Module Management', $r, 'apps', null, 4);
        $this->make($r, 'billing', 'Billing & Invoices', $r, 'cash', null, 5);
        $this->make($r, 'tickets', 'Support Tickets', $r, 'headset', null, 6);
        $this->make($r, 'logs', 'System Logs', $r, 'file-text', null, 7);
        $this->make($r, 'settings', 'Settings', $r, 'settings', null, 8);
    }

    protected function seedAdminPortal(): void
    {
        $portal = 'admin';
        $owner = 'owner,admin,manager';
        $ownerAdmin = 'owner,admin';

        $this->make($portal, 'dashboard', 'Dashboard', $owner, 'grid', 'admin.dashboard', 1);

        $finance = $this->make($portal, 'finance', 'Finance', $ownerAdmin, 'cash', null, 2);
        // Daily-use views come first; configuration screens (Chart of
        // Accounts, Setup) go last, after Reports — this ordering applies
        // to every module's child list.
        $financeRoutes = [
            'Overview' => 'admin.finance.overview.index',
            'Invoices' => 'admin.finance.invoices.index',
            'Payments' => 'admin.finance.payments.index',
            'Ledger' => 'admin.finance.ledger.index',
            'Expenses' => 'admin.finance.expenses.index',
            'Reports' => 'admin.finance.reports.index',
        ];
        foreach (['Overview', 'Invoices', 'Payments', 'Ledger', 'Expenses', 'Reports'] as $i => $label) {
            $this->make($portal, 'finance-'.str($label)->slug(), $label, $ownerAdmin, null, $financeRoutes[$label] ?? null, $i, $finance->id);
        }
        $this->make($portal, 'finance-vendors', 'Vendors', $ownerAdmin, null, 'admin.finance.vendors.index', 6, $finance->id);
        $this->make($portal, 'finance-coa', 'Chart of Accounts', $ownerAdmin, null, 'admin.finance.coa.index', 7, $finance->id);
        $this->make($portal, 'finance-setup', 'Setup', $ownerAdmin, null, 'admin.finance.settings', 8, $finance->id);

        $hr = $this->make($portal, 'hr', 'HR', $owner, 'users', null, 3);
        $hrRoutes = ['Employees' => 'admin.hr.employees.index', 'Attendance' => 'admin.hr.attendance.index', 'Leave Management' => 'admin.hr.leaves.index', 'Payroll' => 'admin.hr.payroll.index'];
        foreach (['Overview', 'Employees', 'Attendance', 'Leave Management', 'Payroll', 'Recruitment', 'Performance'] as $i => $label) {
            $this->make($portal, 'hr-'.str($label)->slug(), $label, $owner, null, $hrRoutes[$label] ?? null, $i, $hr->id);
        }
        $this->make($portal, 'hr-attendance-import', 'Attendance Import', $owner, null, 'admin.hr.attendance.import.index', 7, $hr->id);
        $this->make($portal, 'hr-reports', 'Reports', $owner, null, 'admin.hr.reports.index', 8, $hr->id);
        $this->make($portal, 'hr-setup', 'Setup', $owner, null, 'admin.hr.settings', 9, $hr->id);

        $projects = $this->make($portal, 'projects', 'Projects', $owner, 'layout-kanban', null, 4);
        $projectRoutes = [
            'All Projects' => 'admin.projects.index',
            'Boards' => 'admin.projects.boards.index',
            'Timesheets' => 'admin.projects.timesheets.index',
            'Milestones' => 'admin.projects.milestones.index',
        ];
        foreach (['All Projects', 'Boards', 'Timesheets', 'Milestones'] as $i => $label) {
            $this->make($portal, 'projects-'.str($label)->slug(), $label, $owner, null, $projectRoutes[$label] ?? null, $i + 1, $projects->id);
        }

        // Owner/admin/manager can reach CRM at the route level, same as
        // HR/Projects/Company — the custom-role system (RolePermission) is
        // what actually narrows a given manager down to specific screens
        // and capabilities (e.g. the "Assign" capability on Leads).
        $crm = $this->make($portal, 'crm', 'Leads / CRM', $owner, 'target', null, 5);
        $this->make($portal, 'crm-overview', 'Leads Overview', $owner, null, 'admin.crm.overview.index', 0, $crm->id);
        $this->make($portal, 'crm-leads', 'Leads', $owner, null, 'admin.crm.leads.index', 1, $crm->id);
        $this->make($portal, 'crm-followups', 'Follow-ups', $owner, null, 'admin.crm.followups.index', 2, $crm->id);
        $this->make($portal, 'crm-approvals', 'Approvals', $owner, null, 'admin.crm.leads.approvals', 3, $crm->id);
        $this->make($portal, 'crm-imports', 'Imports', $owner, null, 'admin.crm.imports.index', 4, $crm->id);
        $this->make($portal, 'crm-reports', 'Leads Report', $owner, null, 'admin.crm.reports.index', 5, $crm->id);
        $this->make($portal, 'crm-setup', 'Setup', $owner, null, 'admin.crm.settings', 6, $crm->id);

        $company = $this->make($portal, 'company', 'Company', $owner, 'building-community', null, 6);
        $this->make($portal, 'company-user-role', 'User Role', $ownerAdmin, null, 'admin.company.roles.index', 0, $company->id);
        $companyRoutes = ['Special Holidays' => 'admin.company.holidays.index', 'Clients' => 'admin.company.clients.index'];
        foreach (['Complaints', 'Clients', 'Event Calendar', 'Notice Board', 'Special Holidays'] as $i => $label) {
            $this->make($portal, 'company-'.str($label)->slug(), $label, $owner, null, $companyRoutes[$label] ?? null, $i + 1, $company->id);
        }
        // Audit trail — and employee screen captures below — are sensitive,
        // narrower than the rest of Company, like User Role above.
        $this->make($portal, 'company-log-history', 'Log History', $ownerAdmin, null, 'admin.company.logs.index', 6, $company->id);
        $this->make($portal, 'company-user-working', 'User Working', $ownerAdmin, null, 'admin.company.user-working.index', 7, $company->id);
        $this->make($portal, 'company-settings', 'Settings', $owner, null, 'admin.company.settings', 8, $company->id);

        $this->make($portal, 'chat', 'Chat', $owner, 'message-circle', null, 7);
    }

    protected function seedEmployeePortal(): void
    {
        $portal = 'employee';
        $role = 'employee';

        $this->make($portal, 'dashboard', 'Dashboard', $role, 'grid', 'employee.dashboard', 1);
        $this->make($portal, 'attendance', 'My Attendance', $role, 'calendar-stats', 'employee.attendance.index', 2);
        $this->make($portal, 'tasks', 'My Tasks', $role, 'checklist', 'employee.tasks.index', 3);
        $this->make($portal, 'leads', 'My Leads', $role, 'target', 'employee.leads.index', 4);
        $this->make($portal, 'projects', 'Projects', $role, 'layout-kanban', 'employee.projects.index', 5);
        $this->make($portal, 'leaves', 'Leaves & Holidays', $role, 'beach', 'employee.leaves.index', 6);
        $this->make($portal, 'payslips', 'Payslips', $role, 'cash', null, 7);
        $this->make($portal, 'chat', 'Chat', $role, 'message-circle', null, 8);
        $this->make($portal, 'notices', 'Notice Board', $role, 'speakerphone', null, 9);
        $this->make($portal, 'calendar', 'Event Calendar', $role, 'calendar', null, 10);
        $this->make($portal, 'complaints', 'Complaints', $role, 'alert-circle', null, 11);
        $this->make($portal, 'settings', 'Settings', $role, 'settings', null, 12);
    }
}