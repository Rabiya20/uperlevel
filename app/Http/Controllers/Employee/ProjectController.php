<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $projects = Project::where('tenant_id', $user->tenant_id)
            ->where(fn ($q) => $q->whereHas('tasks', fn ($q2) => $q2->where('assigned_to', $user->id))
                ->orWhereHas('timesheets', fn ($q2) => $q2->where('user_id', $user->id)))
            ->with('client')
            ->orderBy('name')
            ->get();

        return view('employee.projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $user = auth()->user();
        abort_unless($project->tenant_id === $user->tenant_id, 404);

        $tasks = $project->tasks()->where('assigned_to', $user->id)->orderBy('due_date')->get();
        $milestones = $project->milestones()->orderBy('due_date')->get();

        return view('employee.projects.show', compact('project', 'tasks', 'milestones'));
    }
}
