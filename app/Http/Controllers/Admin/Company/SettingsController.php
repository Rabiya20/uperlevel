<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\ScreenCaptureSettings;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        // Grouped by region (Africa, America, Asia, ...) so the dropdown
        // isn't one giant flat list of 400+ identifiers.
        $timezoneGroups = collect(DateTimeZone::listIdentifiers())
            ->groupBy(fn ($tz) => str_contains($tz, '/') ? explode('/', $tz)[0] : 'Other');

        $captureSettings = ScreenCaptureSettings::forTenant($tenant);

        return view('admin.company.settings', compact('tenant', 'timezoneGroups', 'captureSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $data = $request->validate([
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ]);

        $tenant->update($data);

        $captureData = $request->validate([
            'capture_enabled' => ['nullable', 'boolean'],
            'interval_mode' => ['required', 'string', Rule::in(ScreenCaptureSettings::INTERVAL_MODES)],
            'interval_minutes' => ['required_if:interval_mode,fixed', 'nullable', 'integer', 'min:1', 'max:1440'],
            'random_min_minutes' => ['required_if:interval_mode,random', 'nullable', 'integer', 'min:1', 'max:1440'],
            'random_max_minutes' => ['required_if:interval_mode,random', 'nullable', 'integer', 'min:1', 'max:1440', 'gte:random_min_minutes'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'notify_employees' => ['nullable', 'boolean'],
        ]);

        ScreenCaptureSettings::forTenant($tenant)->update([
            'enabled' => (bool) ($captureData['capture_enabled'] ?? false),
            'interval_mode' => $captureData['interval_mode'],
            'interval_minutes' => $captureData['interval_minutes'] ?? 60,
            'random_min_minutes' => $captureData['random_min_minutes'] ?? null,
            'random_max_minutes' => $captureData['random_max_minutes'] ?? null,
            'retention_days' => $captureData['retention_days'] ?? null,
            'notify_employees' => (bool) ($captureData['notify_employees'] ?? false),
        ]);

        return back()->with('status', 'Company settings saved.');
    }
}
