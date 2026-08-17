<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

/**
 * Read-focused hub — the "HR > Salary" nav entry, distinct from the
 * general Employees directory and from the per-employee edit screen
 * (reached from here, still owned by EmployeeSalaryController). Exists so
 * "Salary" is its own, separately permission-checkable module rather than
 * being folded into Employees — see PermissionMatrix::CAPABILITIES.
 */
class SalaryController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $employees = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['employee', 'manager'])
            ->with(['department', 'designation'])
            ->orderBy('name')
            ->get();

        return view('admin.hr.salary.index', compact('employees'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
