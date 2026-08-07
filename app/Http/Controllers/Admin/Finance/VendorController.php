<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $vendors = Vendor::where('tenant_id', $tenant->id)
            ->withCount('expenses')
            ->orderBy('name')
            ->get();

        return view('admin.finance.vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        return view('admin.finance.vendors.create', ['vendor' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $this->validated($request, $tenant->id);

        Vendor::create([...$data, 'tenant_id' => $tenant->id]);

        return redirect()->route('admin.finance.vendors.index')->with('status', 'Vendor added.');
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorizeTenant($vendor);

        return view('admin.finance.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeTenant($vendor);
        $data = $this->validated($request, $vendor->tenant_id, $vendor->id);

        $vendor->update($data);

        return redirect()->route('admin.finance.vendors.index')->with('status', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorizeTenant($vendor);

        if ($vendor->expenses()->exists()) {
            throw ValidationException::withMessages([
                'vendor' => 'This vendor has expenses recorded against it — deactivate it instead of deleting.',
            ]);
        }

        $vendor->delete();

        return redirect()->route('admin.finance.vendors.index')->with('status', 'Vendor removed.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Vendor $vendor): void
    {
        abort_unless($vendor->tenant_id === $this->tenant()->id, 404);
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('vendors', 'name')->where('tenant_id', $tenantId)->ignore($ignoreId)],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
