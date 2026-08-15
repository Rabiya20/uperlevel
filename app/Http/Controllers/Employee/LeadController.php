<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CrmSettings;
use App\Models\FinanceSettings;
use App\Models\Lead;
use App\Models\LeadFollowup;
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
        $user = auth()->user();
        $settings = CrmSettings::forTenant($tenant);

        $leads = Lead::where('tenant_id', $tenant->id)
            ->visibleTo($user)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('company_name', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $mine = Lead::where('tenant_id', $tenant->id)->visibleTo($user);
        $assignedCount = (clone $mine)->count();
        $wonCount = (clone $mine)->where('conversion_status', 'approved')->count();
        $lostCount = (clone $mine)->where('status', $settings->lostStageKey())->count();
        $conversionRate = $assignedCount > 0 ? round($wonCount / $assignedCount * 100) : 0;
        $followupsToday = LeadFollowup::where('tenant_id', $tenant->id)
            ->where('employee_id', $user->id)
            ->whereNull('completed_at')
            ->whereDate('follow_up_at', now()->toDateString())
            ->count();

        return view('employee.leads.index', compact('leads', 'settings', 'assignedCount', 'wonCount', 'lostCount', 'conversionRate', 'followupsToday'));
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $currencyOptions = FinanceSettings::forTenant($tenant)->allCurrencies();

        return view('employee.leads.create', ['settings' => $settings, 'lead' => null, 'isAdmin' => false, 'currencyOptions' => $currencyOptions]);
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
            'assigned_employee_id' => auth()->id(),
        ]);
        $lead->logActivity('created', auth()->user(), 'Lead created.');

        return redirect()->route('employee.leads.show', $lead)->with('status', 'Lead added.');
    }

    public function show(Lead $lead): View
    {
        $lead->authorizeAccess(auth()->user());
        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $lead->load(['activities.user', 'followups.employee', 'assignedEmployee', 'client']);

        return view('employee.leads.show', compact('lead', 'settings') + ['isAdmin' => false]);
    }

    public function edit(Lead $lead): View
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->canEdit(auth()->user()), 403, 'This lead is locked pending approval.');

        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $currencyOptions = FinanceSettings::forTenant($tenant)->allCurrencies();

        return view('employee.leads.edit', compact('lead', 'settings', 'currencyOptions') + ['isAdmin' => false]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        abort_unless($lead->canEdit(auth()->user()), 403, 'This lead is locked pending approval.');

        $tenant = $this->tenant();
        $settings = CrmSettings::forTenant($tenant);
        $data = $this->validated($request, $tenant, $settings);

        $lead->update($data);
        $lead->logActivity('updated', auth()->user(), 'Lead details updated.');

        return redirect()->route('employee.leads.show', $lead)->with('status', 'Lead updated.');
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $lead->authorizeAccess(auth()->user());
        $data = $request->validate(['status' => ['required', 'string']]);

        $client = $lead->changeStatus($data['status'], auth()->user());

        if ($client) {
            $message = $client->wasRecentlyCreated
                ? 'Status updated — lead won, added as a new client ("'.$client->name.'").'
                : 'Status updated — lead won, matched to the existing client "'.$client->name.'".';

            return back()->with('status', $message);
        }

        return back()->with('status', $lead->fresh()->conversion_status === 'pending_approval'
            ? 'Status updated — sent for admin approval.'
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
            'employee_id' => auth()->id(),
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
            'currency' => ['required', 'string', Rule::in(FinanceSettings::forTenant($tenant)->allCurrencies())],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
