<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ScreenCapture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScreenCaptureController extends Controller
{
    /** Defaults to today, every employee — exactly as requested. */
    public function index(Request $request): View
    {
        $tenant = $this->tenant();
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $selectedUserId = $request->integer('user_id') ?: null;

        $captures = ScreenCapture::where('tenant_id', $tenant->id)
            ->whereDate('captured_at', $date->toDateString())
            ->when($selectedUserId, fn ($q) => $q->where('user_id', $selectedUserId))
            ->with('user')
            ->orderBy('captured_at')
            ->get();

        $groups = $captures->groupBy('user_id')->map(fn ($group) => [
            'user' => $group->first()->user,
            'captures' => $group,
        ])->sortBy(fn ($group) => $group['user']->name ?? '');

        $employees = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.company.user-working.index', [
            'groups' => $groups,
            'employees' => $employees,
            'selectedUserId' => $selectedUserId,
            'date' => $date,
        ]);
    }

    public function view(ScreenCapture $screenCapture): StreamedResponse
    {
        $this->authorizeTenant($screenCapture);

        return Storage::disk('local')->response($screenCapture->file_path);
    }

    public function destroy(ScreenCapture $screenCapture): RedirectResponse
    {
        $this->authorizeTenant($screenCapture);
        $screenCapture->loadMissing('user');
        $capturedAt = $screenCapture->captured_at->format('j M Y g:i A');
        $employeeName = $screenCapture->user->name ?? 'Unknown';

        Storage::disk('local')->delete($screenCapture->file_path);
        $screenCapture->delete();

        ActivityLog::record('screen_capture_deleted', "Deleted a screenshot of {$employeeName} captured {$capturedAt}.");

        return back()->with('status', 'Screenshot removed.');
    }

    public function revokeAgent(User $user): RedirectResponse
    {
        $tenant = $this->tenant();
        abort_unless($user->tenant_id === $tenant->id, 404);

        $user->tokens()->where('name', 'screen-agent')->delete();

        ActivityLog::record('screen_agent_revoked', "Revoked {$user->name}'s screen-capture agent access.", $user);

        return back()->with('status', "Revoked {$user->name}'s screen-capture agent access.");
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(ScreenCapture $capture): void
    {
        abort_unless($capture->tenant_id === $this->tenant()->id, 404);
    }
}
