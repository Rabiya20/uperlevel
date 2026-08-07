<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ProjectArchiveController extends Controller
{
    /**
     * Exports every task/milestone/timesheet for a completed, fully-paid
     * project into a signed zip, deletes those rows from the live tables,
     * and marks the project archived. Invoices are never touched.
     */
    public function archive(Project $project): BinaryFileResponse
    {
        $this->authorizeTenant($project);
        abort_unless($project->isArchivable(), 422, 'This project cannot be archived yet — it must be marked completed with every linked invoice paid.');

        $tasks = $project->tasks()->get();
        $milestones = $project->milestones()->get();
        $timesheets = $project->timesheets()->get();

        $manifest = $this->buildManifest($project, $tasks, $milestones, $timesheets);

        $tempPath = tempnam(sys_get_temp_dir(), 'uperlevel_archive_');
        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create the archive file.');
        }
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->close();

        DB::transaction(function () use ($project) {
            Timesheet::where('project_id', $project->id)->delete();
            Task::where('project_id', $project->id)->delete();
            Milestone::where('project_id', $project->id)->delete();
            $project->update(['status' => 'archived', 'archived_at' => now()]);
        });

        ActivityLog::record('archived', "Archived project \"{$project->name}\" — {$tasks->count()} tasks, {$milestones->count()} milestones, {$timesheets->count()} timesheets exported and removed from live data.", $project);

        $filename = Str::slug($project->name).'-archive-'.now()->format('Y-m-d').'.zip';

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /** Re-imports a project's archived data from a zip previously produced by archive() above. */
    public function restore(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeTenant($project);
        abort_unless($project->status === 'archived', 422, 'This project is not archived.');

        $request->validate(['file' => ['required', 'file', 'mimes:zip', 'max:20480']]);

        $manifest = $this->readManifest($request->file('file')->getRealPath());

        if ((int) $manifest['tenant_id'] !== $project->tenant_id || (int) $manifest['project_id'] !== $project->id) {
            throw ValidationException::withMessages(['file' => 'This archive belongs to a different project and can\'t be restored here.']);
        }

        $tenantUserIds = User::where('tenant_id', $project->tenant_id)->pluck('id')->all();

        [$taskCount, $milestoneCount, $timesheetCount] = DB::transaction(function () use ($project, $manifest, $tenantUserIds) {
            $newTaskIds = [];
            foreach ($manifest['tasks'] as $taskData) {
                $task = Task::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'title' => $taskData['title'],
                    'description' => $taskData['description'],
                    'status' => $taskData['status'],
                    'priority' => $taskData['priority'],
                    'due_date' => $taskData['due_date'],
                    'assigned_to' => in_array($taskData['assigned_to'], $tenantUserIds, true) ? $taskData['assigned_to'] : null,
                    'created_by' => in_array($taskData['created_by'], $tenantUserIds, true) ? $taskData['created_by'] : null,
                ]);
                $newTaskIds[] = $task->id;
            }

            foreach ($manifest['milestones'] as $milestoneData) {
                Milestone::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'title' => $milestoneData['title'],
                    'description' => $milestoneData['description'],
                    'due_date' => $milestoneData['due_date'],
                    'status' => $milestoneData['status'],
                    'completed_at' => $milestoneData['completed_at'],
                ]);
            }

            foreach ($manifest['timesheets'] as $timesheetData) {
                Timesheet::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'task_id' => $timesheetData['task_index'] !== null ? ($newTaskIds[$timesheetData['task_index']] ?? null) : null,
                    'user_id' => in_array($timesheetData['user_id'], $tenantUserIds, true) ? $timesheetData['user_id'] : null,
                    'date' => $timesheetData['date'],
                    'hours' => $timesheetData['hours'],
                    'notes' => $timesheetData['notes'],
                ]);
            }

            $project->update([
                'status' => $manifest['project']['status_before_archive'] ?? 'completed',
                'archived_at' => null,
            ]);

            return [count($manifest['tasks']), count($manifest['milestones']), count($manifest['timesheets'])];
        });

        ActivityLog::record('restored', "Restored project \"{$project->name}\" from archive — {$taskCount} tasks, {$milestoneCount} milestones, {$timesheetCount} timesheets recreated.", $project);

        return back()->with('status', "Project restored — {$taskCount} tasks, {$milestoneCount} milestones, {$timesheetCount} timesheets recreated.");
    }

    /** @return array{tasks: array, milestones: array, timesheets: array, tenant_id: int, project_id: int, project: array} */
    private function readManifest(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw ValidationException::withMessages(['file' => 'That file could not be opened as a zip archive.']);
        }

        $json = $zip->getFromName('manifest.json');
        $zip->close();

        $manifest = $json ? json_decode($json, true) : null;

        if (! is_array($manifest) || ! isset($manifest['signature'], $manifest['tenant_id'], $manifest['project_id'])) {
            throw ValidationException::withMessages(['file' => 'That zip file doesn\'t contain a valid UperLevel project archive.']);
        }

        $signature = $manifest['signature'];
        $payload = $manifest;
        unset($payload['signature']);

        if (! hash_equals(hash_hmac('sha256', json_encode($payload), config('app.key')), $signature)) {
            throw ValidationException::withMessages(['file' => 'This archive file appears to be corrupted or was not generated by UperLevel.']);
        }

        return $manifest;
    }

    private function buildManifest(Project $project, $tasks, $milestones, $timesheets): array
    {
        $taskIndexById = [];
        $taskRows = [];
        foreach ($tasks->values() as $i => $task) {
            $taskIndexById[$task->id] = $i;
            $taskRows[] = [
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => optional($task->due_date)->toDateString(),
                'assigned_to' => $task->assigned_to,
                'created_by' => $task->created_by,
            ];
        }

        $milestoneRows = $milestones->map(fn ($m) => [
            'title' => $m->title,
            'description' => $m->description,
            'due_date' => optional($m->due_date)->toDateString(),
            'status' => $m->status,
            'completed_at' => optional($m->completed_at)->toIso8601String(),
        ])->values()->all();

        $timesheetRows = $timesheets->map(fn ($t) => [
            'task_index' => $t->task_id !== null ? ($taskIndexById[$t->task_id] ?? null) : null,
            'user_id' => $t->user_id,
            'date' => optional($t->date)->toDateString(),
            'hours' => (string) $t->hours,
            'notes' => $t->notes,
        ])->values()->all();

        $payload = [
            'uperlevel_archive_version' => 1,
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'archived_at' => now()->toIso8601String(),
            'archived_by' => auth()->id(),
            'project' => [
                'name' => $project->name,
                'status_before_archive' => 'completed',
            ],
            'tasks' => $taskRows,
            'milestones' => $milestoneRows,
            'timesheets' => $timesheetRows,
        ];

        return [...$payload, 'signature' => hash_hmac('sha256', json_encode($payload), config('app.key'))];
    }

    private function authorizeTenant(Project $project): void
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);
        abort_unless($project->tenant_id === $tenant->id, 404);
    }
}
