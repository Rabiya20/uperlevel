<?php

namespace App\Support\Permissions;

/**
 * Which of View/Create/Edit/Delete(/Assign) are actually meaningful for a
 * given module, keyed by Module::key — used by the Add/Edit Role screen to
 * grey out checkboxes that don't correspond to any real route (e.g.
 * Dashboard has no create/edit/delete action anywhere). Derived by auditing
 * each module's routes against EnsureModulePermission::requiredLevel()'s
 * verb mapping (GET→view, POST+".store"→create, PUT/PATCH→edit, DELETE→
 * delete, any other POST→edit). A module not listed here defaults to all
 * four base levels — fail open, so nothing already-correct regresses.
 *
 * "assign" (crm-leads only, for now) is the one level not tied to an HTTP
 * verb — it isn't enforced by EnsureModulePermission at all, but checked
 * directly in Admin\Crm\LeadController, since lead reassignment shares the
 * same store/update route as every other lead field.
 */
class PermissionMatrix
{
    private const CAPABILITIES = [
        // Admin portal
        'dashboard' => ['view'],
        'finance-invoices' => ['view', 'create', 'edit'],
        'finance-coa' => ['view', 'create', 'edit', 'delete'],
        'finance-setup' => ['view', 'edit'],
        'finance-reports' => ['view'],
        'hr-employees' => ['view', 'create', 'edit'],
        'hr-attendance' => ['view', 'edit', 'delete'],
        'hr-leave-management' => ['view', 'edit'],
        'hr-payroll' => ['view', 'edit'],
        'hr-reports' => ['view'],
        'hr-setup' => ['view', 'create', 'edit', 'delete'],
        'projects-all-projects' => ['view', 'create', 'edit'],
        'projects-boards' => ['view', 'create', 'edit', 'delete'],
        'projects-timesheets' => ['view', 'create', 'delete'],
        'projects-milestones' => ['view', 'create', 'edit', 'delete'],
        'crm-overview' => ['view'],
        'crm-leads' => ['view', 'create', 'edit', 'delete', 'assign'],
        'crm-followups' => ['view'],
        'crm-approvals' => ['view'],
        'crm-imports' => ['view', 'edit'],
        'crm-reports' => ['view'],
        'crm-setup' => ['view', 'edit'],
        'company-user-role' => ['view', 'create', 'edit', 'delete'],
        'company-clients' => ['view'],
        'company-special-holidays' => ['view', 'create', 'edit', 'delete'],
        'company-log-history' => ['view'],
        'company-settings' => ['view', 'edit'],

        // Employee portal
        'attendance' => ['view'],
        'tasks' => ['view', 'edit'],
        'leads' => ['view', 'create', 'edit'],
        'projects' => ['view'],
        'leaves' => ['view', 'create'],
    ];

    private const ALL_LEVELS = ['view', 'create', 'edit', 'delete'];

    public static function levelsFor(string $moduleKey): array
    {
        return self::CAPABILITIES[$moduleKey] ?? self::ALL_LEVELS;
    }

    public static function allows(string $moduleKey, string $level): bool
    {
        return in_array($level, self::levelsFor($moduleKey), true);
    }
}
