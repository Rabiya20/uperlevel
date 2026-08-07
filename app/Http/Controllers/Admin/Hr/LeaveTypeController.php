<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $leaveTypes = LeaveType::where('tenant_id', $this->tenant()->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.hr.leave-types.index', compact('leaveTypes'));
    }

    public function create(): View
    {
        $existingTypes = LeaveType::where('tenant_id', $this->tenant()->id)->orderBy('name')->get();

        return view('admin.hr.leave-types.create', ['leaveType' => null, 'existingTypes' => $existingTypes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        LeaveType::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.hr.leave-types.index')->with('status', 'Leave type added.');
    }

    public function edit(LeaveType $leaveType): View
    {
        $this->authorizeTenant($leaveType);

        $existingTypes = LeaveType::where('tenant_id', $leaveType->tenant_id)
            ->where('id', '!=', $leaveType->id)
            ->orderBy('name')
            ->get();

        return view('admin.hr.leave-types.edit', compact('leaveType', 'existingTypes'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeTenant($leaveType);

        $data = $this->validated($request, $leaveType->tenant_id, $leaveType->id);
        $leaveType->update($data);

        return redirect()->route('admin.hr.leave-types.index')->with('status', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeTenant($leaveType);

        if (LeaveRequest::where('leave_type_id', $leaveType->id)->exists()) {
            throw ValidationException::withMessages([
                'leaveType' => 'This leave type has requests recorded against it — deactivate it instead of deleting.',
            ]);
        }

        $leaveType->delete();

        return redirect()->route('admin.hr.leave-types.index')->with('status', 'Leave type removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(LeaveType $leaveType): void
    {
        abort_unless($leaveType->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('leave_types', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:365'],
            'max_per_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'max_carry_forward_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_accumulation_days' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $data['carry_forward'] = $request->boolean('carry_forward');
        $data['is_encashable'] = $request->boolean('is_encashable');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
