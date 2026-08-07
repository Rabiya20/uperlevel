<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Statuses an admin can pick directly. "archived" is deliberately excluded
     * here — it's only ever set by ProjectArchiveController::archive(), which
     * also exports and clears the project's task/milestone/timesheet rows, so
     * letting this form set it directly would leave the project's status out
     * of sync with its actual data.
     */
    public const STATUSES = ['planning', 'active', 'on_hold', 'completed'];

    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $projects = Project::where('tenant_id', $tenant->id)
            ->with(['client', 'manager'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('manager_id'), fn ($q) => $q->where('manager_id', $request->manager_id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $clients = Client::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $managers = $this->managerOptions($tenant->id);

        return view('admin.projects.index', compact('projects', 'clients', 'managers'));
    }

    public function boards(): View
    {
        $tenant = $this->tenant();

        $projects = Project::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'archived')
            ->withCount(['tasks'])
            ->orderBy('name')
            ->get();

        return view('admin.projects.boards', compact('projects'));
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $clients = Client::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $managers = $this->managerOptions($tenant->id);

        return view('admin.projects.create', compact('clients', 'managers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        $project = Project::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.projects.show', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project): View
    {
        $this->authorizeTenant($project);

        $clients = Client::where('tenant_id', $project->tenant_id)->orderBy('name')->get();
        $managers = $this->managerOptions($project->tenant_id);

        return view('admin.projects.edit', compact('project', 'clients', 'managers'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeTenant($project);
        abort_if($project->status === 'archived', 422, 'An archived project must be restored before it can be edited.');

        $data = $this->validated($request, $project->tenant_id);
        $project->update($data);

        return redirect()->route('admin.projects.show', $project)->with('status', 'Project updated.');
    }

    public function show(Project $project): View
    {
        $this->authorizeTenant($project);
        $project->load(['client', 'manager', 'creator']);

        $taskCounts = $project->tasks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $milestones = $project->milestones()->orderBy('due_date')->get();
        $timesheetHours = $project->timesheets()->sum('hours');
        $invoices = $project->invoices()->latest()->get();

        return view('admin.projects.show', compact('project', 'taskCounts', 'milestones', 'timesheetHours', 'invoices'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Project $project): void
    {
        abort_unless($project->tenant_id === $this->tenant()->id, 404);
    }

    private function managerOptions(int $tenantId)
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['owner', 'admin', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    private function validated(Request $request, int $tenantId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('tenant_id', $tenantId)],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
    }
}
