<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimesheetController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $timesheets = Timesheet::whereHas('project', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->with(['project', 'user', 'task'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->latest('date')
            ->paginate(30)
            ->withQueryString();

        $projects = Project::where('tenant_id', $tenant->id)->where('status', '!=', 'archived')->orderBy('name')->get();
        $users = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.projects.timesheets.index', compact('timesheets', 'projects', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenant->id)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $project = Project::where('tenant_id', $tenant->id)->findOrFail($data['project_id']);
        abort_if($project->status === 'archived', 422, 'This project is archived — restore it first.');

        Timesheet::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);

        return back()->with('status', 'Time logged.');
    }

    public function destroy(Timesheet $timesheet): RedirectResponse
    {
        abort_unless($timesheet->tenant_id === $this->tenant()->id, 404);

        $timesheet->delete();

        return back()->with('status', 'Entry removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
