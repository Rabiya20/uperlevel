<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function index(): View
    {
        $designations = Designation::where('tenant_id', $this->tenant()->id)
            ->withCount('users')
            ->with('department')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.hr.designations.index', compact('designations'));
    }

    public function create(): View
    {
        $departments = Department::where('tenant_id', $this->tenant()->id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.hr.designations.create', ['designation' => null, 'departments' => $departments]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        Designation::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.hr.designations.index')->with('status', 'Designation added.');
    }

    public function edit(Designation $designation): View
    {
        $this->authorizeTenant($designation);

        $departments = Department::where('tenant_id', $designation->tenant_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.hr.designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $this->authorizeTenant($designation);

        $data = $this->validated($request, $designation->tenant_id, $designation->id);
        $designation->update($data);

        return redirect()->route('admin.hr.designations.index')->with('status', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $this->authorizeTenant($designation);

        if (User::where('designation_id', $designation->id)->exists()) {
            throw ValidationException::withMessages([
                'designation' => 'This designation has employees assigned — deactivate it instead of deleting.',
            ]);
        }

        $designation->delete();

        return redirect()->route('admin.hr.designations.index')->with('status', 'Designation removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Designation $designation): void
    {
        abort_unless($designation->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('designations', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
