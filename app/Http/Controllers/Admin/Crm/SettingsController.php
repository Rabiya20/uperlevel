<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmSettings;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $settings = CrmSettings::forTenant($tenant);

        return view('admin.crm.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $data = $request->validate([
            'pipeline_stages' => ['required', 'array', 'min:1'],
            'pipeline_stages.*.label' => ['required', 'string', 'max:40'],
            'pipeline_stages.*.key' => ['nullable', 'string', 'max:40'],
            'pipeline_stages.*.is_won' => ['nullable', 'boolean'],
            'pipeline_stages.*.is_lost' => ['nullable', 'boolean'],
            'lead_sources' => ['nullable', 'array'],
            'lead_sources.*' => ['nullable', 'string', 'max:60'],
            'assignment_mode' => ['required', 'string', 'in:manual,round_robin,rule_based'],
            'rule_based_field' => ['nullable', 'string', 'in:country,source,budget'],
            'approval_required' => ['nullable', 'boolean'],
            'duplicate_handling' => ['required', 'string', 'in:skip,allow'],
        ]);

        $settings = CrmSettings::forTenant($tenant);
        $existingKeys = collect($settings->pipeline_stages)->pluck('key')->all();

        $wonIndex = collect($data['pipeline_stages'])->search(fn ($s) => (bool) ($s['is_won'] ?? false));
        $lostIndex = collect($data['pipeline_stages'])->search(fn ($s) => (bool) ($s['is_lost'] ?? false));

        if ($wonIndex === false || $lostIndex === false) {
            throw ValidationException::withMessages([
                'pipeline_stages' => 'Pick exactly one "Won" stage and one "Lost" stage.',
            ]);
        }

        // Existing stages keep their key (labels can be renamed freely
        // without orphaning leads already sitting in that stage); brand
        // new rows get one slugified from the label.
        $usedKeys = [];
        $stages = [];
        foreach ($data['pipeline_stages'] as $i => $row) {
            $key = $row['key'] ?? null;
            if (blank($key)) {
                $key = $original = Str::slug($row['label']) ?: 'stage';
                $n = 2;
                while (in_array($key, $usedKeys, true)) {
                    $key = $original.'-'.$n++;
                }
            }
            $usedKeys[] = $key;
            $stages[] = [
                'key' => $key,
                'label' => $row['label'],
                'is_won' => $i === $wonIndex,
                'is_lost' => $i === $lostIndex,
            ];
        }

        $removedKeys = array_diff($existingKeys, array_column($stages, 'key'));
        if ($removedKeys && Lead::where('tenant_id', $tenant->id)->whereIn('status', $removedKeys)->exists()) {
            throw ValidationException::withMessages([
                'pipeline_stages' => 'One of the removed stages still has leads in it — move them first.',
            ]);
        }

        $data['pipeline_stages'] = $stages;
        $data['lead_sources'] = array_values(array_filter($data['lead_sources'] ?? [], fn ($s) => filled($s)));
        $data['approval_required'] = (bool) ($data['approval_required'] ?? false);

        $settings->update($data);

        return back()->with('status', 'CRM settings saved.');
    }
}
