<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Overrides the framework default (which redirects to a hardcoded
     * "/home" route that doesn't exist in this app) so an already-logged-in
     * user hitting /login is sent straight to their role's dashboard.
     */
    public function handle($request, Closure $next, string ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                return redirect()->to(match (true) {
                    $user->isSuperAdmin() => route('superadmin.companies.index'),
                    $user->isEmployee() => route('employee.dashboard'),
                    default => route('admin.dashboard'),
                });
            }
        }

        return $next($request);
    }
}
