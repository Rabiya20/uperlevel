<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $logs = ActivityLog::where('tenant_id', $tenant->id)
            ->with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.$request->q.'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $users = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);
        $actions = ActivityLog::where('tenant_id', $tenant->id)->distinct()->orderBy('action')->pluck('action');

        return view('admin.company.logs.index', compact('logs', 'users', 'actions'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
