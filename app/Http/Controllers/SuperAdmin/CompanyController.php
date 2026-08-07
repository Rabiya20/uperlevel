<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount('users')->orderBy('name')->get();

        return view('superadmin.companies.index', compact('tenants'));
    }

    /**
     * Superadmin enters a tenant's Admin Portal without needing that
     * tenant's credentials. We never log in "as" one of their users —
     * instead we open a support session scoped to this tenant.
     */
    public function enter(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->session()->put('active_tenant_id', $tenant->id);

        return redirect()->route('admin.dashboard');
    }

    public function exitImpersonation(Request $request): RedirectResponse
    {
        $request->session()->forget('active_tenant_id');

        return redirect()->route('superadmin.companies.index');
    }
}
