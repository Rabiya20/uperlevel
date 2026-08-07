<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\HrSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /** Default: current month, own attendance only. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $settings = HrSettings::forTenant($tenant);
        [$start, $end] = $this->monthRange($request->input('month'));

        $dayStatuses = Attendance::dayStatusesFor($user, $start, $end, $settings);
        $stats = Attendance::summarize($dayStatuses, $settings);

        return view('employee.attendance.index', compact('user', 'settings', 'dayStatuses', 'start', 'end', 'stats'));
    }

    private function monthRange(?string $month): array
    {
        $start = $month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth() : now()->startOfMonth();

        return [$start->copy(), $start->copy()->endOfMonth()];
    }
}
