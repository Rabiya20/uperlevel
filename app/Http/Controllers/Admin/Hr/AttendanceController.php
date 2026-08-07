<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\HrSettings;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /** Default: today, every user in the tenant. */
    public function index(Request $request): View
    {
        $tenant = $this->tenant();
        $settings = HrSettings::forTenant($tenant);
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $users = User::where('tenant_id', $tenant->id)
            ->with('shift')
            ->when($request->filled('user_id'), fn ($q) => $q->where('id', $request->user_id))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $attendanceByUser = Attendance::where('tenant_id', $tenant->id)
            ->where('work_date', $date->toDateString())
            ->whereIn('user_id', $users->pluck('id'))
            ->with('shift')
            ->get()
            ->keyBy('user_id');

        $shifts = Shift::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $allUsers = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

        $onLeaveByUser = LeaveRequest::whereIn('user_id', $users->pluck('id'))
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('leaveType')
            ->get()
            ->keyBy('user_id');

        $holidayToday = Holiday::where('tenant_id', $tenant->id)->whereDate('date', $date)->first();

        return view('admin.hr.attendance.index', compact(
            'users', 'attendanceByUser', 'date', 'settings', 'shifts', 'allUsers', 'onLeaveByUser', 'holidayToday'
        ));
    }

    /** That person's current-month calendar + stats, same shape the employee sees for themselves. */
    public function show(Request $request, User $user): View
    {
        $tenant = $this->tenant();
        abort_unless($user->tenant_id === $tenant->id, 404);

        $settings = HrSettings::forTenant($tenant);
        [$start, $end] = $this->monthRange($request->input('month'));

        $dayStatuses = Attendance::dayStatusesFor($user, $start, $end, $settings);
        $stats = Attendance::summarize($dayStatuses, $settings);

        $attendanceUserId = $user->id;
        $isAdmin = true;

        return view('admin.hr.attendance.show', compact(
            'user', 'settings', 'dayStatuses', 'start', 'end', 'stats', 'attendanceUserId', 'isAdmin'
        ));
    }

    public function assignShift(Request $request, User $user): RedirectResponse
    {
        $tenant = $this->tenant();
        abort_unless($user->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ]);

        if ($data['shift_id'] ?? null) {
            $shift = Shift::findOrFail($data['shift_id']);
            abort_unless($shift->tenant_id === $tenant->id, 404);
        }

        $user->update(['shift_id' => $data['shift_id'] ?? null]);

        return back()->with('status', $user->name.'\'s shift updated.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function monthRange(?string $month): array
    {
        $start = $month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth() : now()->startOfMonth();

        return [$start->copy(), $start->copy()->endOfMonth()];
    }
}
