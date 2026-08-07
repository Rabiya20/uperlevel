<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::where('tenant_id', $this->tenant()->id)
            ->withCount('users')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.hr.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('admin.hr.departments.create', ['department' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        Department::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.hr.departments.index')->with('status', 'Department added.');
    }

    public function edit(Department $department): View
    {
        $this->authorizeTenant($department);

        return view('admin.hr.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeTenant($department);

        $data = $this->validated($request, $department->tenant_id, $department->id);
        $department->update($data);

        return redirect()->route('admin.hr.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorizeTenant($department);

        if (User::where('department_id', $department->id)->exists()) {
            throw ValidationException::withMessages([
                'department' => 'This department has employees assigned — deactivate it instead of deleting.',
            ]);
        }

        $department->delete();

        return redirect()->route('admin.hr.departments.index')->with('status', 'Department removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Department $department): void
    {
        abort_unless($department->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('departments', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
