<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeSalaryController extends Controller
{
    public function edit(User $employee): View
    {
        $this->authorizeTenant($employee);

        // Reaching this page only requires "view" (see requiredLevel() in
        // EnsureModulePermission) — pass canEdit through so the view can
        // render read-only for a viewer who wasn't also granted "edit".
        $canEdit = Module::userHasCapability(auth()->user(), 'hr-salary', 'edit');

        return view('admin.hr.employees.salary', compact('employee', 'canEdit'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeTenant($employee);

        $data = $request->validate([
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $employee->update($data);

        ActivityLog::record('salary_updated', "Updated basic salary for \"{$employee->name}\".", $employee);

        return redirect()->route('admin.hr.employees.salary.edit', $employee)->with('status', 'Salary saved.');
    }

    private function authorizeTenant(User $employee): void
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);
        abort_unless($employee->tenant_id === $tenant->id, 404);
    }
}
