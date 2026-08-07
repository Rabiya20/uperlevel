<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::where('tenant_id', $this->tenant()->id)
            ->withCount('users')
            ->orderBy('portal')
            ->orderBy('name')
            ->get();

        return view('admin.company.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.company.roles.create', [
            'role' => null,
            'adminModules' => $this->routableModules('admin'),
            'employeeModules' => $this->routableModules('employee'),
            'permissions' => collect(),
            'copyTemplates' => $this->copyTemplates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        $role = Role::create([...$data, 'tenant_id' => $tenant->id]);
        $this->savePermissions($request, $role);

        return redirect()->route('admin.company.roles.index')->with('status', 'Role added.');
    }

    public function edit(Role $role): View
    {
        $this->authorizeTenant($role);

        return view('admin.company.roles.edit', [
            'role' => $role,
            'adminModules' => $this->routableModules('admin'),
            'employeeModules' => $this->routableModules('employee'),
            'permissions' => $role->permissions()->get()->keyBy('module_id'),
            'copyTemplates' => $this->copyTemplates($role->id),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeTenant($role);

        $data = $this->validated($request, $role->tenant_id, $role->id);
        $role->update($data);
        $this->savePermissions($request, $role);

        return redirect()->route('admin.company.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeTenant($role);

        if (User::where('role_id', $role->id)->exists()) {
            throw ValidationException::withMessages([
                'role' => 'This role is assigned to one or more users — reassign them to another role first.',
            ]);
        }

        $role->delete();

        return redirect()->route('admin.company.roles.index')->with('status', 'Role removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Role $role): void
    {
        abort_unless($role->tenant_id === $this->tenant()->id, 404);
    }

    /**
     * Every module actually reachable in the given portal (has a route),
     * grouped by top-level module for the permission matrix: a top-level
     * module with routable children renders as a group header + its
     * children as rows; a top-level module with its own route and no
     * routable children (e.g. Dashboard) renders as a single-row group of
     * itself. Top-level modules with neither (e.g. Chat, still unbuilt)
     * are omitted entirely.
     *
     * @return Collection<int, array{top: Module, rows: Collection<int, Module>}>
     */
    private function routableModules(string $portal): Collection
    {
        $topLevel = Module::forPortal($portal)
            ->topLevel()
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        $groups = collect();

        foreach ($topLevel as $parent) {
            $routableChildren = $parent->children->filter(fn (Module $c) => $c->route_name)->values();

            if ($routableChildren->isNotEmpty()) {
                $groups->push(['top' => $parent, 'rows' => $routableChildren]);
            } elseif ($parent->route_name) {
                $groups->push(['top' => $parent, 'rows' => collect([$parent])]);
            }
        }

        return $groups;
    }

    /** Every routable module (admin or employee) as a flat id list — savePermissions() writes/prunes against this. */
    private function routableModuleIds(string $portal): Collection
    {
        return $this->routableModules($portal)->flatMap(fn ($group) => $group['rows'])->pluck('id');
    }

    /**
     * The tenant's existing roles + their saved permissions, serialized for
     * the "Copy permissions from" dropdown's JS to consume — excludes the
     * role currently being edited (copying from yourself is a no-op).
     */
    private function copyTemplates(?int $excludeRoleId = null): Collection
    {
        return Role::where('tenant_id', $this->tenant()->id)
            ->when($excludeRoleId, fn ($q) => $q->where('id', '!=', $excludeRoleId))
            ->with('permissions')
            ->orderBy('portal')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'portal' => $r->portal,
                'permissions' => $r->permissions->mapWithKeys(fn (RolePermission $p) => [
                    $p->module_id => [
                        'view' => $p->can_view,
                        'create' => $p->can_create,
                        'edit' => $p->can_edit,
                        'delete' => $p->can_delete,
                    ],
                ]),
            ]);
    }

    private function savePermissions(Request $request, Role $role): void
    {
        $moduleIds = $this->routableModuleIds($role->portal);

        foreach ($moduleIds as $moduleId) {
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'module_id' => $moduleId],
                [
                    'can_view' => $request->boolean("permissions.{$moduleId}.view"),
                    'can_create' => $request->boolean("permissions.{$moduleId}.create"),
                    'can_edit' => $request->boolean("permissions.{$moduleId}.edit"),
                    'can_delete' => $request->boolean("permissions.{$moduleId}.delete"),
                ]
            );
        }

        // Only relevant if the role's portal changed on edit — drop any
        // leftover permission rows for modules outside the new portal.
        RolePermission::where('role_id', $role->id)->whereNotIn('module_id', $moduleIds)->delete();
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('roles', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'portal' => ['required', Rule::in(['admin', 'employee'])],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
