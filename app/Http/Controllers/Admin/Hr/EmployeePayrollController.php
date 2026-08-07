<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmployeePayrollComponent;
use App\Models\PayrollComponent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePayrollController extends Controller
{
    public function edit(User $employee): View
    {
        $this->authorizeTenant($employee);

        $components = PayrollComponent::where('tenant_id', $employee->tenant_id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $assigned = EmployeePayrollComponent::where('user_id', $employee->id)
            ->pluck('amount', 'payroll_component_id');

        $totals = $this->totals($components, $assigned);

        return view('admin.hr.employees.payroll', compact('employee', 'components', 'assigned', 'totals'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeTenant($employee);

        $data = $request->validate([
            'amounts' => ['nullable', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $componentIds = PayrollComponent::where('tenant_id', $employee->tenant_id)->pluck('id');

        foreach ($componentIds as $componentId) {
            $amount = $data['amounts'][$componentId] ?? null;

            if ($amount === null || $amount === '') {
                EmployeePayrollComponent::where('user_id', $employee->id)->where('payroll_component_id', $componentId)->delete();

                continue;
            }

            EmployeePayrollComponent::updateOrCreate(
                ['user_id' => $employee->id, 'payroll_component_id' => $componentId],
                ['tenant_id' => $employee->tenant_id, 'amount' => $amount]
            );
        }

        ActivityLog::record('payroll_updated', "Updated payroll structure for \"{$employee->name}\".", $employee);

        return redirect()->route('admin.hr.employees.payroll.edit', $employee)->with('status', 'Payroll structure saved.');
    }

    private function authorizeTenant(User $employee): void
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);
        abort_unless($employee->tenant_id === $tenant->id, 404);
    }

    private function totals($components, $assigned): array
    {
        $payable = $deductible = 0;

        foreach ($components as $component) {
            $amount = (float) ($assigned[$component->id] ?? 0);
            if ($component->isEarning()) {
                $payable += $amount;
            } else {
                $deductible += $amount;
            }
        }

        return ['payable' => $payable, 'deductible' => $deductible, 'net' => $payable - $deductible];
    }
}
