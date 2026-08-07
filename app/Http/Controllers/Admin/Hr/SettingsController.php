<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\HrSettings;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $settings = HrSettings::forTenant($tenant);
        $shiftCount = Shift::where('tenant_id', $tenant->id)->count();
        $leaveTypeCount = LeaveType::where('tenant_id', $tenant->id)->count();
        $departmentCount = Department::where('tenant_id', $tenant->id)->count();
        $designationCount = Designation::where('tenant_id', $tenant->id)->count();
        $payrollComponentCount = PayrollComponent::where('tenant_id', $tenant->id)->count();
        $nextEmployeeCode = User::nextEmployeeCode($tenant);

        return view('admin.hr.settings', compact(
            'settings', 'shiftCount', 'leaveTypeCount', 'departmentCount', 'designationCount',
            'payrollComponentCount', 'nextEmployeeCode'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $data = $request->validate([
            'overtime_enabled' => ['nullable', 'boolean'],
            'overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
            'overtime_threshold_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'min:0', 'max:6'],
            'payroll_cycle' => ['required', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'payroll_pay_day' => ['required', 'integer', 'min:1', 'max:28'],
            'employee_code_prefix' => ['nullable', 'string', 'max:20'],
        ]);

        $data['overtime_enabled'] = (bool) ($data['overtime_enabled'] ?? false);
        $data['working_days'] = array_values(array_unique(array_map('intval', $data['working_days'] ?? [])));

        HrSettings::forTenant($tenant)->update($data);

        return back()->with('status', 'HR settings saved.');
    }

    public function lockPayroll(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $data = $request->validate(['payroll_locked_through' => ['required', 'date']]);

        HrSettings::forTenant($tenant)->update(['payroll_locked_through' => $data['payroll_locked_through']]);

        return back()->with('status', 'Payroll locked through '.Carbon::parse($data['payroll_locked_through'])->format('M j, Y').'.');
    }

    public function unlockPayroll(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        HrSettings::forTenant($tenant)->update(['payroll_locked_through' => null]);

        return back()->with('status', 'Payroll unlocked.');
    }
}
