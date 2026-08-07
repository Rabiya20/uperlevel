<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Resolves the "current tenant" for this request:
     *  - normal users        -> their own tenant_id
     *  - superadmin browsing -> the tenant they chose via "Login as" (session)
     * Also shares $currentTenant / $isImpersonating / $modules with every view
     * so layouts stay DRY (no controller has to pass these manually).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $tenant = null;
        $isImpersonating = false;

        if ($user->isSuperAdmin() && session()->has('active_tenant_id')) {
            $tenant = Tenant::find(session('active_tenant_id'));
            $isImpersonating = true;
        } elseif ($user->tenant_id) {
            $tenant = $user->tenant;
        }

        $portal = match (true) {
            $user->isSuperAdmin() && ! $isImpersonating => 'superadmin',
            $user->isEmployee() => 'employee',
            default => 'admin',
        };

        // When impersonating, show the tenant's owner-level navigation
        // regardless of the superadmin's own role.
        $effectiveRole = $isImpersonating ? \App\Models\User::ROLE_OWNER : $user->role;

        // Impersonating superadmins act as the tenant's owner and aren't
        // subject to any real user's custom role restrictions.
        $customRoleId = $isImpersonating ? null : $user->role_id;
        $modules = Module::navFor($portal, $effectiveRole, $tenant, $customRoleId);

        app()->instance('currentTenant', $tenant);

        View::share('currentTenant', $tenant);
        View::share('isImpersonating', $isImpersonating);
        View::share('modules', $modules);
        View::share('portal', $portal);

        return $next($request);
    }
}
