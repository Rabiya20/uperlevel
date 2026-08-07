<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayrollComponent;
use App\Models\PayrollComponent;
use App\Models\User;
use Illuminate\View\View;

/**
 * Read-focused hub — the "HR > Payroll" nav entry, distinct from Setup
 * (which configures the payroll components themselves) and from the
 * per-employee edit screen (reached from here, still owned by
 * EmployeePayrollController). Exists so "Payroll" is a real, separately
 * permission-checkable module rather than being folded into Employees.
 */
class PayrollController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $employees = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['employee', 'manager'])
            ->with(['department', 'designation'])
            ->orderBy('name')
            ->get();

        $components = PayrollComponent::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        $totals = $employees->mapWithKeys(function (User $employee) use ($components) {
            $assigned = EmployeePayrollComponent::where('user_id', $employee->id)->pluck('amount', 'payroll_component_id');

            $payable = $deductible = 0;
            foreach ($components as $component) {
                $amount = (float) ($assigned[$component->id] ?? 0);
                if ($component->isEarning()) {
                    $payable += $amount;
                } else {
                    $deductible += $amount;
                }
            }

            return [$employee->id => ['payable' => $payable, 'deductible' => $deductible, 'net' => $payable - $deductible]];
        });

        return view('admin.hr.payroll.index', compact('employees', 'totals'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
