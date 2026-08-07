<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmSettings;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadImportBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);

        $leads = Lead::where('tenant_id', $tenant->id)
            ->with('assignedEmployee')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('assigned_employee_id'), fn ($q) => $q->where('assigned_employee_id', $request->assigned_employee_id))
            ->when($request->filled('conversion_status'), fn ($q) => $q->where('conversion_status', $request->conversion_status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('company_name', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalLeads = Lead::where('tenant_id', $tenant->id)->count();
        $wonCount = Lead::where('tenant_id', $tenant->id)->where('conversion_status', 'approved')->count();
        $pendingApprovals = Lead::where('tenant_id', $tenant->id)->where('conversion_status', 'pending_approval')->count();
        $pendingImports = LeadImportBatch::where('tenant_id', $tenant->id)->where('status', 'pending_review')->count();
        $conversionRate = $totalLeads > 0 ? round($wonCount / $totalLeads * 100) : 0;

        $employees = User::where('tenant_id', $tenant->id)->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get();

        return view('admin.crm.leads.index', compact('leads', 'settings', 'employees', 'totalLeads', 'wonCount', 'pendingApprovals', 'pendingImports', 'conversionRate'));
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $employees = User::where('tenant_id', $tenant->id)->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get();

        return view('admin.crm.leads.create', ['settings' => $settings, 'employees' => $employees, 'lead' => null, 'isAdmin' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $data = $this->validated($request, $tenant, $settings);

        $duplicate = Lead::findDuplicate($tenant->id, $data['email'] ?? null, $data['phone'] ?? null, $data['company_name'] ?? null);
        if ($duplicate && $settings->duplicate_handling === 'skip') {
            throw ValidationException::withMessages([
                'email' => 'A lead with matching email, phone or company already exists: '.$duplicate->name.' (#'.$duplicate->id.').',
            ]);
        }

        $lead = Lead::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $settings->firstStageKey(),
            'created_by' => auth()->id(),
        ]);
        $lead->logActivity('created', auth()->user(), 'Lead created.');

        return redirect()->route('admin.crm.leads.show', $lead)->with('status', 'Lead added.');
    }

    public function show(Lead $lead): View
    {
        $lead->authorizeAccess(auth()->user());
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $employees = User::where('tenant_id', $tenant->id)->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get();
        $lead->load(['activities.user', 'followups.employee', 'assignedEmployee', 'client']);

        return view('admin.crm.leads.show', compact('lead', 'settings', 'employees') + ['isAdmin' => true]);
    }

    public function edit(Lead $lead): View
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->canEdit(auth()->user()), 403, 'This lead is locked pending approval.');

        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $employees = User::where('tenant_id', $tenant->id)->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get();

        return view('admin.crm.leads.edit', compact('lead', 'settings', 'employees') + ['isAdmin' => true]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->canEdit(auth()->user()), 403, 'This lead is locked pending approval.');

        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $data = $this->validated($request, $tenant, $settings);

        $reassigned = array_key_exists('assigned_employee_id', $data)
            && $data['assigned_employee_id'] != $lead->assigned_employee_id;
        $previousAssignee = $lead->assignedEmployee?->name ?? 'Unassigned';

        $lead->update($data);

        if ($reassigned) {
            $newAssignee = $lead->fresh()->assignedEmployee?->name ?? 'Unassigned';
            $lead->logActivity('assignment', auth()->user(), "Reassigned from {$previousAssignee} to {$newAssignee}.");
        } else {
            $lead->logActivity('updated', auth()->user(), 'Lead details updated.');
        }

        return redirect()->route('admin.crm.leads.show', $lead)->with('status', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        $lead->delete();

        return redirect()->route('admin.crm.leads.index')->with('status', 'Lead removed.');
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        $data = $request->validate(['status' => ['required', 'string']]);

        $client = $lead->changeStatus($data['status'], auth()->user());

        if ($client) {
            return back()->with('status', $this->conversionMessage($client, 'Status updated — lead won'));
        }

        return back()->with('status', $lead->fresh()->conversion_status === 'pending_approval'
            ? 'Status updated — sent for approval.'
            : 'Status updated.');
    }

    public function addActivity(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        $data = $request->validate([
            'type' => ['required', 'string', 'in:note,call'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $lead->logActivity($data['type'], auth()->user(), $data['description']);

        return back()->with('status', $data['type'] === 'call' ? 'Call logged.' : 'Note added.');
    }

    public function storeFollowup(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        $data = $request->validate([
            'follow_up_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        LeadFollowup::create([
            ...$data,
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'employee_id' => $lead->assigned_employee_id ?? auth()->id(),
        ]);

        return back()->with('status', 'Follow-up scheduled.');
    }

    public function completeFollowup(Lead $lead, LeadFollowup $followup): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($followup->lead_id === $lead->id, 404);

        $followup->update(['completed_at' => now()]);

        return back()->with('status', 'Follow-up marked complete.');
    }

    public function followupsIndex(Request $request): View
    {
        $tenant = $this->tenant();

        $followups = LeadFollowup::where('tenant_id', $tenant->id)
            ->with(['lead', 'employee'])
            ->whereNull('completed_at')
            ->orderBy('follow_up_at')
            ->paginate(30);

        return view('admin.crm.followups.index', compact('followups'));
    }

    public function approve(Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->conversion_status === 'pending_approval', 422, 'This lead has nothing awaiting approval.');

        $client = $lead->convertToClient(auth()->user());

        return back()->with('status', $this->conversionMessage($client, 'Lead approved and converted to a client'));
    }

    /** Shared "intimation" text for both the direct-win and approval-queue conversion paths. */
    private function conversionMessage(Client $client, string $prefix): string
    {
        return $client->wasRecentlyCreated
            ? $prefix.' — added as a new client ("'.$client->name.'").'
            : $prefix.' — matched to the existing client "'.$client->name.'".';
    }

    /** Nav shortcut for the pending-approval queue — reuses index()'s filtering/pagination/KPIs. */
    public function approvals(): RedirectResponse
    {
        return redirect()->route('admin.crm.leads.index', ['conversion_status' => 'pending_approval']);
    }

    public function reject(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->conversion_status === 'pending_approval', 422, 'This lead has nothing awaiting approval.');

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $lead->rejectConversion(auth()->user(), $data['reason'] ?? null);

        return back()->with('status', 'Conversion rejected — sent back to the employee.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function validated(Request $request, $tenant, CrmSettings $settings): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', Rule::in($settings->lead_sources ?: [])],
            'country' => ['nullable', 'string', 'max:60'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:3000'],
            'assigned_employee_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenant->id)->where('role', User::ROLE_EMPLOYEE),
            ],
        ]);
    }
}
