<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MilestoneController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $milestones = Milestone::whereHas('project', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->with('project')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('due_date')
            ->paginate(30)
            ->withQueryString();

        $projects = Project::where('tenant_id', $tenant->id)->where('status', '!=', 'archived')->orderBy('name')->get();

        return view('admin.projects.milestones.index', compact('milestones', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $project = Project::where('tenant_id', $tenant->id)->findOrFail($data['project_id']);
        abort_if($project->status === 'archived', 422, 'This project is archived — restore it first.');

        Milestone::create([...$data, 'tenant_id' => $tenant->id]);

        return back()->with('status', 'Milestone added.');
    }

    public function complete(Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project->tenant_id === $this->tenant()->id, 404);

        $milestone->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('status', 'Milestone marked complete.');
    }

    public function destroy(Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project->tenant_id === $this->tenant()->id, 404);

        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
