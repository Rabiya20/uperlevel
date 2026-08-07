<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $shifts = Shift::where('tenant_id', $tenant->id)
            ->withCount('employees')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.hr.shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('admin.hr.shifts.create', ['shift' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        Shift::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.hr.shifts.index')->with('status', 'Shift added.');
    }

    public function edit(Shift $shift): View
    {
        $this->authorizeTenant($shift);

        return view('admin.hr.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorizeTenant($shift);

        $data = $this->validated($request, $shift->tenant_id, $shift->id);
        $shift->update($data);

        return redirect()->route('admin.hr.shifts.index')->with('status', 'Shift updated.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->authorizeTenant($shift);

        $inUse = User::where('shift_id', $shift->id)->exists()
            || Attendance::where('shift_id', $shift->id)->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'shift' => 'This shift has employees or attendance history attached — deactivate it instead of deleting.',
            ]);
        }

        $shift->delete();

        return redirect()->route('admin.hr.shifts.index')->with('status', 'Shift removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Shift $shift): void
    {
        abort_unless($shift->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('shifts', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
