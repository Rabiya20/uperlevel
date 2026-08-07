<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'total_tenants' => Tenant::count(),
        ];

        $recentTenants = Tenant::withCount('users')->latest()->take(5)->get();

        return view('superadmin.dashboard', compact('stats', 'recentTenants'));
    }
}
