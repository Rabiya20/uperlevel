<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrSettings;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveController extends Controller
{
    /** Default: this year's balances for everyone, plus pending requests. */
    public function index(Request $request): View
    {
        $tenant = $this->tenant();
        $year = (int) ($request->query('year') ?: now()->year);
        $status = $request->query('status', 'pending');

        $leaveTypes = LeaveType::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $balances = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'types' => $leaveTypes->map(fn (LeaveType $type) => [
                    'type' => $type,
                    'balance' => $type->balanceFor($user, $year),
                ]),
            ]);

        $requestsQuery = LeaveRequest::with(['user', 'leaveType'])->where('tenant_id', $tenant->id);
        if ($status !== 'all') {
            $requestsQuery->where('status', $status);
        }
        $requests = $requestsQuery->orderByDesc('start_date')->paginate(20)->withQueryString();

        $pendingCount = LeaveRequest::where('tenant_id', $tenant->id)->where('status', 'pending')->count();

        return view('admin.hr.leaves.index', compact('balances', 'leaveTypes', 'requests', 'status', 'year', 'pendingCount'));
    }

    /** One employee's full balance breakdown + request history. */
    public function show(Request $request, User $user): View
    {
        $tenant = $this->tenant();
        abort_unless($user->tenant_id === $tenant->id, 404);

        $year = (int) ($request->query('year') ?: now()->year);

        $leaveTypes = LeaveType::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $balances = $leaveTypes->map(fn (LeaveType $type) => [
            'type' => $type,
            'balance' => $type->balanceFor($user, $year),
        ]);

        $history = LeaveRequest::where('user_id', $user->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->get();

        return view('admin.hr.leaves.show', compact('user', 'balances', 'history', 'year'));
    }

    public function approve(LeaveRequest $leave): RedirectResponse
    {
        $this->authorizeTenant($leave);
        abort_if($leave->status !== 'pending', 422);
        $this->guardLock($leave);

        $leave->update(['status' => 'approved', 'decided_by' => auth()->id(), 'decided_at' => now(), 'decision_note' => null]);

        return back()->with('status', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorizeTenant($leave);
        abort_if($leave->status !== 'pending', 422);

        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);

        $leave->update([
            'status' => 'rejected',
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ]);

        return back()->with('status', 'Leave request declined.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(LeaveRequest $leave): void
    {
        abort_unless($leave->tenant_id === $this->tenant()->id, 404);
    }

    private function guardLock(LeaveRequest $leave): void
    {
        $settings = HrSettings::forTenant($this->tenant());

        if ($settings->isPayrollLocked($leave->start_date) || $settings->isPayrollLocked($leave->end_date)) {
            throw ValidationException::withMessages([
                'leave' => 'Payroll is locked through '.$settings->payroll_locked_through->format('M j, Y').' — unlock it in HR Setup before approving this request.',
            ]);
        }
    }
}
