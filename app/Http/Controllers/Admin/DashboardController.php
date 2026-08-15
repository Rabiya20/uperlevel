<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CrmSettings;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadImportBatch;
use App\Models\Module;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('currentTenant');
        $user = auth()->user();

        // The nav module list is already filtered (tenant-enabled modules +
        // base role + custom-role RolePermission.can_view) by SetTenantContext
        // — reuse it rather than re-deriving access another way, so dashboard
        // widgets always agree with what's in the sidebar for this viewer.
        $moduleKeys = view()->shared('modules', collect())->pluck('key');
        $canViewCrm = $moduleKeys->contains('crm');
        $canViewFinance = $moduleKeys->contains('finance');
        $canViewProjects = $moduleKeys->contains('projects');
        $canViewHr = $moduleKeys->contains('hr');

        $todayAttendanceCount = $tenant
            ? Attendance::where('tenant_id', $tenant->id)
                ->whereDate('work_date', now()->toDateString())
                ->whereNotNull('check_in')
                ->count()
            : 0;

        $teamSize = $tenant ? $tenant->users()->count() : 0;
        $attendanceRate = $teamSize > 0 ? round(($todayAttendanceCount / $teamSize) * 100) : 0;

        $todayAttendanceByUser = collect();
        if ($tenant) {
            $tenant->load(['users' => fn ($q) => $q->orderBy('name')]);
            $todayAttendanceByUser = Attendance::where('tenant_id', $tenant->id)
                ->where('work_date', now()->toDateString())
                ->with('shift')
                ->get()
                ->keyBy('user_id');
        }

        $pendingLeadApprovals = 0;
        $pendingImports = 0;

        // Only worth querying (and only worth showing on the dashboard) if
        // this tenant actually has CRM switched on and the viewer can act
        // on it — managers see the module but can't approve anything in it.
        if ($tenant && $canViewCrm && $user->isOwnerOrAdmin()) {
            $crmModule = Module::forPortal('admin')->where('key', 'crm')->first();
            $crmEnabled = $crmModule && in_array($crmModule->id, $tenant->enabledModuleIds(), true);

            if ($crmEnabled) {
                $pendingLeadApprovals = Lead::where('tenant_id', $tenant->id)->where('conversion_status', 'pending_approval')->count();
                $pendingImports = LeadImportBatch::where('tenant_id', $tenant->id)->where('status', 'pending_review')->count();
            }
        }

        $recentLeads = collect();
        $leadStats = null;
        $crmSettings = null;
        if ($tenant && $canViewCrm) {
            $leadsQuery = Lead::where('tenant_id', $tenant->id)->visibleTo($user);
            $recentLeads = (clone $leadsQuery)->latest()->take(5)->with('assignedEmployee')->get();
            $leadStats = [
                'total' => (clone $leadsQuery)->count(),
                'new_this_week' => (clone $leadsQuery)->where('created_at', '>=', now()->subDays(7))->count(),
            ];
            $crmSettings = CrmSettings::forTenant($tenant);
        }

        // "Pending" here means billed but not yet paid off (status "sent") —
        // there's no literal "pending" status stored, draft/cancelled don't count.
        $accountsStats = null;
        $dueInvoicesThisMonth = collect();
        if ($tenant && $canViewFinance) {
            $pendingInvoices = Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'sent')
                ->withSum('payments', 'amount')
                ->get();

            // Invoices can be billed in different currencies (tenant's base
            // + whatever else is enabled in Finance Setup) — summed per
            // currency so amounts are never added together across currencies.
            $accountsStats = [
                'pending_count' => $pendingInvoices->count(),
                'pending_by_currency' => $pendingInvoices
                    ->groupBy('currency')
                    ->map(fn ($group) => $group->sum(fn (Invoice $i) => max(0, (float) $i->total - (float) ($i->payments_sum_amount ?? 0)))),
            ];

            $dueInvoicesThisMonth = Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'sent')
                ->whereBetween('due_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->with('client')
                ->latest()
                ->take(5)
                ->get();
        }

        $deadlineProjects = collect();
        if ($tenant && $canViewProjects) {
            $deadlineProjects = Project::where('tenant_id', $tenant->id)
                ->whereNull('archived_at')
                ->whereBetween('end_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->with('manager')
                ->orderBy('end_date')
                ->take(5)
                ->get();
        }

        return view('admin.dashboard', compact(
            'tenant', 'todayAttendanceCount', 'teamSize', 'todayAttendanceByUser', 'pendingLeadApprovals', 'pendingImports',
            'canViewCrm', 'canViewFinance', 'canViewProjects', 'canViewHr',
            'attendanceRate', 'recentLeads', 'leadStats', 'crmSettings',
            'accountsStats', 'dueInvoicesThisMonth', 'deadlineProjects'
        ));
    }
}
