<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        $tasks = Task::where('assigned_to', auth()->id())
            ->with('project')
            ->orderBy('due_date')
            ->get()
            ->groupBy('status');

        return view('employee.tasks.index', compact('tasks'));
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->assigned_to === auth()->id(), 404);

        $data = $request->validate(['status' => ['required', Rule::in(Task::STATUSES)]]);
        $task->update($data);

        return back()->with('status', 'Task updated.');
    }
}
