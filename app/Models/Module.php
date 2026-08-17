<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'parent_id', 'portal', 'key', 'name', 'icon', 'route_name', 'roles', 'sort_order',
    ];

    /**
     * Some screens are reached via a link from another module's page rather
     * than having their own nav entry (e.g. Shift/Leave Type/Department
     * management, all linked from HR's Setup; per-employee payroll editing,
     * linked from the Payroll hub; Kanban task actions and project
     * archive/restore, reached from a project's own page) — map their
     * route-name *prefix* to the module `key` that owns them. Longest
     * matching prefix wins (see matchPrefixMap()), so a more specific entry
     * like "admin.hr.employees.payroll" correctly overrides the more
     * general "admin.hr.employees" group it's nested under. Shared by
     * nav-highlighting (admin-subnav) and permission enforcement
     * (EnsureModulePermission via resolveByRoute()), so both agree on who
     * "owns" a given screen.
     */
    public const EXTRA_ROUTE_GROUPS = [
        'admin.hr.shifts' => 'hr-setup',
        'admin.hr.leave-types' => 'hr-setup',
        'admin.hr.departments' => 'hr-setup',
        'admin.hr.designations' => 'hr-setup',
        'admin.hr.payroll-components' => 'hr-setup',
        'admin.hr.employees.payroll' => 'hr-payroll',
        'admin.hr.employees.salary' => 'hr-salary',
        'admin.projects.tasks' => 'projects-boards',
        'admin.projects.archive' => 'projects-all-projects',
        'admin.projects.restore' => 'projects-all-projects',
    ];

    /**
     * Purely cosmetic nav-highlighting overrides, for routes whose name
     * doesn't share their "real" submodule's route group — e.g. the
     * leads-import screens live under the "admin.crm.leads.*" route
     * namespace (so they'd otherwise highlight "Leads") but conceptually
     * belong to the "Imports" submodule. Keyed by route-name prefix,
     * longest match wins. Display-only: unlike EXTRA_ROUTE_GROUPS this is
     * NOT consulted by resolveByRoute()/permission enforcement.
     */
    public const NAV_ACTIVE_OVERRIDES = [
        'admin.crm.leads.import' => 'crm-imports',
    ];

    /** Longest-prefix match of $routeName against a [prefix => key] map — shared by both override lookups below. */
    public static function matchPrefixMap(?string $routeName, array $map): ?string
    {
        if (! $routeName) {
            return null;
        }

        $best = null;
        foreach ($map as $prefix => $key) {
            $matches = $routeName === $prefix || str_starts_with($routeName, $prefix.'.');
            if ($matches && (! $best || strlen($prefix) > strlen($best[0]))) {
                $best = [$prefix, $key];
            }
        }

        return $best[1] ?? null;
    }

    public static function navActiveOverride(?string $routeName): ?string
    {
        return self::matchPrefixMap($routeName, self::NAV_ACTIVE_OVERRIDES);
    }

    /** Shared by admin-subnav (header layout) and module-nav (sidebar layout) so both agree. */
    public static function isChildActive(self $child, ?string $currentRoute): bool
    {
        if (! $currentRoute) {
            return false;
        }

        if ($override = self::navActiveOverride($currentRoute) ?? self::matchPrefixMap($currentRoute, self::EXTRA_ROUTE_GROUPS)) {
            return $child->key === $override;
        }

        $group = self::routeGroup($currentRoute);

        return $child->route_name === $currentRoute
            || ($group !== null && self::routeGroup($child->route_name) === $group);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('sort_order');
    }

    /** Only the main-level (non-submodule) nav items. */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForPortal(Builder $query, string $portal): Builder
    {
        return $query->where('portal', $portal);
    }

    /** Roles is stored as a csv string, e.g. "owner,admin,manager". */
    public function scopeVisibleToRole(Builder $query, string $role): Builder
    {
        return $query->where(function ($q) use ($role) {
            $q->where('roles', $role)
                ->orWhere('roles', 'like', "{$role},%")
                ->orWhere('roles', 'like', "%,{$role},%")
                ->orWhere('roles', 'like', "%,{$role}");
        });
    }

    public function allowedFor(string $role): bool
    {
        return in_array($role, array_map('trim', explode(',', $this->roles)), true);
    }

    /**
     * Whether $user has a given capability (view/create/edit/delete/assign)
     * on a module by key — for capabilities that aren't enforced by a plain
     * route middleware check alone (e.g. a field that's only conditionally
     * shown/editable within an otherwise shared screen). Mirrors
     * EnsureModulePermission's own resolution: a user with no custom role
     * gets the base-role default (any admin-portal role passes); a user
     * with a custom role is governed entirely by that role's grant on this
     * module, regardless of their base role.
     */
    public static function userHasCapability(User $user, string $moduleKey, string $level, string $portal = 'admin'): bool
    {
        if (! $user->role_id) {
            return in_array($user->role, User::ADMIN_PORTAL_ROLES, true);
        }

        $module = static::forPortal($portal)->where('key', $moduleKey)->first();

        return (bool) ($module && RolePermission::where('role_id', $user->role_id)->where('module_id', $module->id)->value("can_{$level}"));
    }

    public static function routeGroup(?string $route, int $depth = 3): ?string
    {
        return $route ? implode('.', array_slice(explode('.', $route), 0, $depth)) : null;
    }

    /**
     * Finds the module that "owns" a given route name, for a given portal —
     * either directly (its own route, or a shared 3-segment route group with
     * one of its children) or indirectly via EXTRA_ROUTE_GROUPS. Used by the
     * permission-enforcement middleware; null means the route isn't mapped
     * to any module, so permission checks can't meaningfully apply to it.
     */
    public static function resolveByRoute(string $routeName, string $portal): ?self
    {
        if ($key = self::matchPrefixMap($routeName, self::EXTRA_ROUTE_GROUPS)) {
            return static::forPortal($portal)->where('key', $key)->first();
        }

        $group = self::routeGroup($routeName);

        return static::forPortal($portal)
            ->whereNotNull('route_name')
            ->get()
            ->first(fn (Module $m) => $m->route_name === $routeName || self::routeGroup($m->route_name) === $group);
    }

    /**
     * Build the nav tree for a given portal + role, already filtered to the
     * tenant's enabled modules (submodules always follow their parent's
     * enabled state). When $customRoleId is set, this narrows the tree
     * further to only what that role's permissions allow to be viewed —
     * it can only ever restrict, never grant beyond the base role/tenant.
     *
     * @return \Illuminate\Support\Collection<int, Module>
     */
    public static function navFor(string $portal, string $role, ?Tenant $tenant, ?int $customRoleId = null): \Illuminate\Support\Collection
    {
        $query = static::query()
            ->forPortal($portal)
            ->topLevel()
            ->visibleToRole($role)
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->visibleToRole($role)]);

        $items = $query->get();

        if ($tenant && $portal !== 'superadmin') {
            $enabledIds = $tenant->enabledModuleIds();
            $items = $items->filter(fn (Module $m) => in_array($m->id, $enabledIds, true))->values();
        }

        if ($customRoleId) {
            $viewableIds = RolePermission::where('role_id', $customRoleId)->where('can_view', true)->pluck('module_id');

            $items = $items
                ->map(function (Module $m) use ($viewableIds) {
                    $m->setRelation('children', $m->children->filter(fn (Module $c) => $viewableIds->contains($c->id))->values());

                    return $m;
                })
                ->filter(fn (Module $m) => $viewableIds->contains($m->id) || $m->children->isNotEmpty())
                ->values();
        }

        return $items;
    }
}
