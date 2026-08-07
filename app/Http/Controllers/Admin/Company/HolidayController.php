<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::where('tenant_id', $this->tenant()->id)
            ->orderByDesc('date')
            ->paginate(25);

        return view('admin.company.holidays.index', compact('holidays'));
    }

    public function create(): View
    {
        return view('admin.company.holidays.create', ['holiday' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        Holiday::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.company.holidays.index')->with('status', 'Holiday added.');
    }

    public function edit(Holiday $holiday): View
    {
        $this->authorizeTenant($holiday);

        return view('admin.company.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorizeTenant($holiday);

        $holiday->update($this->validated($request, $holiday->tenant_id, $holiday->id));

        return redirect()->route('admin.company.holidays.index')->with('status', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorizeTenant($holiday);
        $holiday->delete();

        return redirect()->route('admin.company.holidays.index')->with('status', 'Holiday removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Holiday $holiday): void
    {
        abort_unless($holiday->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date' => [
                'required', 'date',
                Rule::unique('holidays', 'date')->where('tenant_id', $tenantId)->ignore($ignoreId),
            ],
        ]);
    }
}
