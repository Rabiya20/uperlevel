<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    // Roles this screen may assign — owner/admin/superadmin accounts are
    // provisioned elsewhere, not through the HR employee directory.
    private const ASSIGNABLE_ROLES = ['employee', 'manager'];

    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $employees = User::where('tenant_id', $tenant->id)
            ->whereIn('role', self::ASSIGNABLE_ROLES)
            ->with(['shift', 'department', 'designation'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('employee_code', 'like', "%{$term}%"));
            })
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $departments = Department::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.hr.employees.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $managers = $this->managerOptions($tenant->id);
        $shifts = Shift::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('tenant_id', $tenant->id)->where('is_active', true)->with('department')->orderBy('name')->get();
        $suggestedCode = User::nextEmployeeCode($tenant);
        $roles = $this->roleOptions($tenant->id);

        return view('admin.hr.employees.create', [
            'employee' => null, 'managers' => $managers, 'shifts' => $shifts,
            'departments' => $departments, 'designations' => $designations, 'suggestedCode' => $suggestedCode, 'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        $password = Str::password(12, symbols: false);

        $employee = User::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'password' => Hash::make($password),
        ]);

        ActivityLog::record('created', "Added employee \"{$employee->name}\" ({$employee->role}).", $employee);

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('status', $employee->name.' added.')
            ->with('generated_password', $password);
    }

    public function edit(User $employee): View
    {
        $this->authorizeManageable($employee);

        $managers = $this->managerOptions($employee->tenant_id, $employee->id);
        $shifts = Shift::where('tenant_id', $employee->tenant_id)->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('tenant_id', $employee->tenant_id)->where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('tenant_id', $employee->tenant_id)->where('is_active', true)->with('department')->orderBy('name')->get();
        $roles = $this->roleOptions($employee->tenant_id);

        return view('admin.hr.employees.edit', compact('employee', 'managers', 'shifts', 'departments', 'designations', 'roles'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeManageable($employee);

        $data = $this->validated($request, $employee->tenant_id, $employee->id);
        $changes = collect($data)->filter(fn ($value, $key) => $employee->{$key} != $value);
        $employee->update($data);

        if ($changes->isNotEmpty()) {
            $summary = $changes->keys()->implode(', ');
            ActivityLog::record('updated', "Updated employee \"{$employee->name}\" — changed: {$summary}.", $employee, $changes->toArray());
        }

        return redirect()->route('admin.hr.employees.show', $employee)->with('status', 'Profile updated.');
    }

    public function show(User $employee): View
    {
        $this->authorizeManageable($employee);
        $employee->load(['shift', 'reportingManager', 'department', 'designation', 'customRole']);

        return view('admin.hr.employees.show', compact('employee'));
    }

    public function resetPassword(User $employee): RedirectResponse
    {
        $this->authorizeManageable($employee);

        $password = Str::password(12, symbols: false);
        $employee->update(['password' => Hash::make($password)]);

        ActivityLog::record('password_reset', "Reset password for \"{$employee->name}\".", $employee);

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('status', 'Password reset.')
            ->with('generated_password', $password);
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    /**
     * Tenant + role check — this directory (and its role dropdown, which
     * only ever offers Employee/Manager) is not equipped to safely edit an
     * owner/admin/superadmin account, so those are out of scope here
     * entirely rather than risking a silent role downgrade on save.
     */
    private function authorizeManageable(User $employee): void
    {
        abort_unless($employee->tenant_id === $this->tenant()->id, 404);
        abort_unless(in_array($employee->role, self::ASSIGNABLE_ROLES, true), 404);
    }

    private function managerOptions(int $tenantId, ?int $excludeId = null)
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['owner', 'admin', 'manager'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    private function roleOptions(int $tenantId)
    {
        return Role::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('portal')->orderBy('name')->get();
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($ignoreId)],
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
            'employee_code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('users', 'employee_code')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'date_of_joining' => ['nullable', 'date'],
            'employment_type' => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'employment_status' => ['required', Rule::in(array_keys(User::EMPLOYMENT_STATUSES))],
            'cnic' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'reporting_manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
            // Both required together — a lone override on one side would
            // leave the other half of the range meaningless.
            'custom_start_time' => ['nullable', 'date_format:H:i', 'required_with:custom_end_time'],
            'custom_end_time' => ['nullable', 'date_format:H:i', 'required_with:custom_start_time'],
            'role_id' => ['nullable', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'machine_name' => ['nullable', 'string', 'max:100', Rule::unique('users', 'machine_name')->ignore($ignoreId)],
        ]);

        if (isset($data['machine_name'])) {
            $data['machine_name'] = filled($data['machine_name']) ? trim($data['machine_name']) : null;
        }

        if (($data['reporting_manager_id'] ?? null) == $ignoreId) {
            $data['reporting_manager_id'] = null; // can't report to yourself
        }

        if (empty($data['shift_id'])) {
            // A custom range only means something relative to an assigned
            // shift — never keep one stranded without the other.
            $data['custom_start_time'] = null;
            $data['custom_end_time'] = null;
        }

        if ($data['role_id'] ?? null) {
            $customRole = Role::find($data['role_id']);
            $expectedPortal = $data['role'] === 'manager' ? 'admin' : 'employee';
            if ($customRole->portal !== $expectedPortal) {
                throw ValidationException::withMessages([
                    'role_id' => 'That role belongs to the '.($customRole->portal === 'admin' ? 'Admin' : 'Employee').' Portal, which doesn\'t match this person\'s Role ('.ucfirst($data['role']).').',
                ]);
            }
        }

        return $data;
    }
}
