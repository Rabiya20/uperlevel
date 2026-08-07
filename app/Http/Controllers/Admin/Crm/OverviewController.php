<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmSettings;
use App\Models\Lead;
use App\Models\LeadActivityLog;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);

        $totalLeads = Lead::where('tenant_id', $tenant->id)->count();
        $newThisWeek = Lead::where('tenant_id', $tenant->id)->where('created_at', '>=', now()->startOfWeek())->count();
        $wonKey = $settings->wonStageKey();
        $lostKey = $settings->lostStageKey();
        $wonCount = $wonKey ? Lead::where('tenant_id', $tenant->id)->where('status', $wonKey)->count() : 0;
        $lostCount = $lostKey ? Lead::where('tenant_id', $tenant->id)->where('status', $lostKey)->count() : 0;
        $inPipeline = $totalLeads - $wonCount - $lostCount;
        $conversionRate = $totalLeads > 0 ? round($wonCount / $totalLeads * 100) : 0;

        $stageBreakdown = collect($settings->pipeline_stages)->map(fn ($stage) => [
            'label' => $stage['label'],
            'count' => Lead::where('tenant_id', $tenant->id)->where('status', $stage['key'])->count(),
        ]);

        $topAssignees = Lead::where('tenant_id', $tenant->id)
            ->whereNotNull('assigned_employee_id')
            ->with('assignedEmployee')
            ->get()
            ->groupBy('assigned_employee_id')
            ->map(fn ($leads) => [
                'name' => $leads->first()->assignedEmployee->name ?? 'Unknown',
                'total' => $leads->count(),
                'won' => $wonKey ? $leads->where('status', $wonKey)->count() : 0,
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $recentActivity = LeadActivityLog::whereHas('lead', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->with(['lead', 'user'])
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('admin.crm.overview.index', compact(
            'totalLeads', 'newThisWeek', 'inPipeline', 'wonCount', 'lostCount', 'conversionRate',
            'stageBreakdown', 'topAssignees', 'recentActivity'
        ));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
