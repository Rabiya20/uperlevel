<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayrollComponent;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollComponentController extends Controller
{
    public function index(): View
    {
        $components = PayrollComponent::where('tenant_id', $this->tenant()->id)
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.hr.payroll-components.index', compact('components'));
    }

    public function create(): View
    {
        return view('admin.hr.payroll-components.create', ['component' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        PayrollComponent::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.hr.payroll-components.index')->with('status', 'Payroll component added.');
    }

    public function edit(PayrollComponent $payrollComponent): View
    {
        $this->authorizeTenant($payrollComponent);

        return view('admin.hr.payroll-components.edit', ['component' => $payrollComponent]);
    }

    public function update(Request $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        $this->authorizeTenant($payrollComponent);

        $data = $this->validated($request, $payrollComponent->tenant_id, $payrollComponent->id);
        $payrollComponent->update($data);

        return redirect()->route('admin.hr.payroll-components.index')->with('status', 'Payroll component updated.');
    }

    public function destroy(PayrollComponent $payrollComponent): RedirectResponse
    {
        $this->authorizeTenant($payrollComponent);

        if (EmployeePayrollComponent::where('payroll_component_id', $payrollComponent->id)->exists()) {
            throw ValidationException::withMessages([
                'component' => 'This component is assigned to one or more employees — deactivate it instead of deleting.',
            ]);
        }

        $payrollComponent->delete();

        return redirect()->route('admin.hr.payroll-components.index')->with('status', 'Payroll component removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(PayrollComponent $component): void
    {
        abort_unless($component->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('payroll_components', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
