<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\HrSettings;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $year = now()->year;

        $leaveTypes = LeaveType::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $balances = $leaveTypes->map(fn (LeaveType $type) => [
            'type' => $type,
            'balance' => $type->balanceFor($user, $year),
        ]);

        $history = LeaveRequest::where('user_id', $user->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->get();

        return view('employee.leaves.index', compact('leaveTypes', 'balances', 'history', 'year'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $data = $request->validate([
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('tenant_id', $tenant->id)->where('is_active', true),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = HrSettings::forTenant($tenant);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($settings->isPayrollLocked($start) || $settings->isPayrollLocked($end)) {
            throw ValidationException::withMessages([
                'start_date' => 'Payroll is locked through '.$settings->payroll_locked_through->format('M j, Y').' — that period can\'t be requested.',
            ]);
        }

        $days = LeaveRequest::computeDays($settings, $start, $end);
        if ($days < 1) {
            throw ValidationException::withMessages(['end_date' => 'Selected range has no working days.']);
        }

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $balance = $leaveType->balanceFor($user, $start->year);
        if ($days > $balance['remaining']) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Only '.$balance['remaining'].' day(s) remaining for '.$leaveType->name.'.',
            ]);
        }

        // Attributed to the start date's month — a request spanning two
        // months counts entirely against the month it starts in.
        if ($leaveType->max_per_month !== null) {
            $usedThisMonth = $leaveType->usedDaysInMonth($user, $start->year, $start->month);
            if ($usedThisMonth + $days > $leaveType->max_per_month) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'This would exceed the '.$leaveType->max_per_month.'-day monthly limit for '.$leaveType->name.' ('.$usedThisMonth.' already used this month).',
                ]);
            }
        }

        LeaveRequest::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('employee.leaves.index')->with('status', 'Leave request submitted.');
    }
}
