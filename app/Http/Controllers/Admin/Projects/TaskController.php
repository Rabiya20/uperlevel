<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Project $project): View
    {
        $this->authorizeTenant($project);

        $tasks = $project->tasks()->with('assignee')->orderBy('due_date')->get()->groupBy('status');
        $assignees = User::where('tenant_id', $project->tenant_id)
            ->whereIn('role', ['employee', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.projects.tasks.index', compact('project', 'tasks', 'assignees'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeTenant($project);
        abort_if($project->status === 'archived', 422, 'This project is archived — restore it first.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $project->tenant_id)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
        ]);

        Task::create([
            ...$data,
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'created_by' => auth()->id(),
            'status' => 'todo',
        ]);

        return back()->with('status', 'Task added.');
    }

    public function update(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorizeTenant($project);
        abort_unless($task->project_id === $project->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);

        $task->update($data);

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $this->authorizeTenant($project);
        abort_unless($task->project_id === $project->id, 404);

        $task->delete();

        return back()->with('status', 'Task removed.');
    }

    private function authorizeTenant(Project $project): void
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);
        abort_unless($project->tenant_id === $tenant->id, 404);
    }
}
