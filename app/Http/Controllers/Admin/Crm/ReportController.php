<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmSettings;
use App\Models\Lead;
use App\Support\Reports\ExportsTabularReports;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsTabularReports;

    public function index(Request $request): View
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->leadsData($start, $end);

        return view('admin.crm.reports.leads', array_merge($data, ['start' => $start, 'end' => $end]));
    }

    public function leadsExport(Request $request, string $format): Response
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->leadsData($start, $end);

        return $this->export(
            'Leads Report',
            $start->format('M j, Y').' – '.$end->format('M j, Y'),
            $data['headers'], $data['rows'], $format
        );
    }

    private function leadsData(Carbon $start, Carbon $end): array
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $wonKey = $settings->wonStageKey();

        $leads = Lead::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('assignedEmployee')
            ->orderBy('created_at')
            ->get();

        $headers = ['Name', 'Company', 'Email', 'Phone', 'Source', 'Status', 'Assigned To', 'Created At', 'Converted'];

        $rows = $leads->map(fn (Lead $lead) => [
            $lead->name, $lead->company_name ?? '—', $lead->email ?? '—', $lead->phone ?? '—',
            $lead->source ?? '—', $settings->stageLabel($lead->status), $lead->assignedEmployee->name ?? '—',
            $lead->created_at->format('j M Y'), $lead->status === $wonKey ? 'Yes' : 'No',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function dateRange(Request $request): array
    {
        $start = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $end = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        return [$start, $end];
    }
}
