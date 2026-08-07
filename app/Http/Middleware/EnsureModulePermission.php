<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    // Routes every logged-in user needs regardless of custom-role
    // permissions, so a restrictive role can never lock someone out of
    // their own portal entirely.
    private const ALWAYS_ALLOWED = ['admin.dashboard', 'employee.dashboard'];

    /**
     * Layered on top of the existing role:owner,admin,... middleware, not a
     * replacement for it. Only acts when the user has a custom Role
     * assigned (users.role_id) — everyone else (i.e. every user today,
     * since this is a brand new opt-in feature) passes through unaffected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role_id) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        if (! $routeName || in_array($routeName, self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        $portal = explode('.', $routeName)[0];
        if (! in_array($portal, ['admin', 'employee'], true)) {
            return $next($request);
        }

        $module = Module::resolveByRoute($routeName, $portal);
        if (! $module) {
            // Not mapped to any module — nothing meaningful to restrict.
            return $next($request);
        }

        $permission = RolePermission::where('role_id', $user->role_id)->where('module_id', $module->id)->first();
        $level = $this->requiredLevel($request);

        if (! $permission || ! $permission->{"can_{$level}"}) {
            abort(403, 'Your role does not have access to this.');
        }

        return $next($request);
    }

    private function requiredLevel(Request $request): string
    {
        return match (true) {
            $request->isMethod('GET') => 'view',
            $request->isMethod('POST') && str($request->route()?->getName())->endsWith('.store') => 'create',
            $request->isMethod('PUT') || $request->isMethod('PATCH') => 'edit',
            $request->isMethod('DELETE') => 'delete',
            default => 'edit', // custom mutating actions (approve/reject/assign-shift/etc.)
        };
    }
}
