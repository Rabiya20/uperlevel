<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\FinanceSettings;
use App\Models\Project;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\ExpenseApproved;
use App\Notifications\ExpenseRejected;
use App\Notifications\ExpenseSubmittedForApproval;
use App\Support\Ledger\LedgerPoster;
use App\Support\Notifications\SafeNotifier;
use App\Support\Reports\ExportsTabularReports;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    use ExportsTabularReports;

    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $expenses = $this->filtered($request, $tenant->id)
            ->with(['vendor', 'paymentAccount'])
            ->latest('expense_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.expenses.index', [
            'expenses' => $expenses,
            'vendors' => Vendor::where('tenant_id', $tenant->id)->orderBy('name')->get(),
            'paymentAccounts' => $this->paymentAccounts($tenant->id),
        ]);
    }

    public function exportList(Request $request, string $format): Response
    {
        $tenant = $this->tenant();

        $expenses = $this->filtered($request, $tenant->id)->with(['vendor', 'paymentAccount'])->orderBy('expense_date')->get();

        $headers = ['Expense No.', 'Date', 'Vendor', 'Payment Account', 'Subtotal', 'Tax', 'Total', 'Status'];
        $rows = $expenses->map(fn (Expense $e) => [
            $e->expense_number,
            $e->expense_date->format('j M Y'),
            $e->vendor->name ?? '—',
            $e->paymentAccount->name ?? '—',
            number_format((float) $e->subtotal, 2),
            number_format((float) $e->tax_amount, 2),
            number_format((float) $e->total_amount, 2),
            ucfirst(str_replace('_', ' ', $e->status)),
        ])->all();

        return $this->export('Expenses', 'All expenses', $headers, $rows, $format);
    }

    public function create(): View
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        return view('admin.finance.expenses.create', [
            'expense' => null,
            'nextNumber' => $settings->nextExpenseNumberPreview(),
        ] + $this->formOptions($tenant->id, $settings));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $data = $this->validatedHeader($request, $tenant->id);
        $lines = $this->validatedLines($request, $tenant->id);
        $this->assertWithinMax($lines, $data, $settings);

        // Locking the settings row for the transaction's duration prevents
        // two concurrent submits from both reading the same expense_number
        // before either commits (see the identical fix on Invoice::store()).
        $expense = DB::transaction(function () use ($tenant, $data, $lines, $request) {
            $lockedSettings = FinanceSettings::where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();

            $expense = Expense::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'expense_number' => $lockedSettings->consumeNextExpenseNumber(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $expense->lines()->create($line);
            }
            $expense->recalculateTotals();

            $this->storeAttachments($request, $expense);

            return $expense;
        });

        ActivityLog::record('created', "Created expense {$expense->expense_number}.", $expense);

        return redirect()->route('admin.finance.expenses.show', $expense)->with('status', 'Expense '.$expense->expense_number.' saved as a draft.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->isEditable(), 422, 'This expense is locked — only draft or pending-approval expenses can be edited.');

        $expense->load('lines', 'attachments');
        $settings = FinanceSettings::forTenant($this->tenant());

        return view('admin.finance.expenses.edit', ['expense' => $expense] + $this->formOptions($expense->tenant_id, $settings));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->isEditable(), 422, 'This expense is locked — only draft or pending-approval expenses can be edited.');

        $data = $this->validatedHeader($request, $expense->tenant_id);
        $lines = $this->validatedLines($request, $expense->tenant_id);
        $reason = $request->validate(['edit_reason' => ['required', 'string', 'max:500']])['edit_reason'];
        $settings = FinanceSettings::forTenant($this->tenant());
        $this->assertWithinMax($lines, $data, $settings);

        DB::transaction(function () use ($expense, $data, $lines, $reason, $request) {
            $expense->update([...$data, 'updated_by' => auth()->id(), 'edit_reason' => $reason]);

            $expense->lines()->delete();
            foreach ($lines as $line) {
                $expense->lines()->create($line);
            }
            $expense->recalculateTotals();

            $this->storeAttachments($request, $expense);
        });

        ActivityLog::record('updated', "Updated expense {$expense->expense_number} — {$reason}", $expense);

        return redirect()->route('admin.finance.expenses.show', $expense)->with('status', 'Expense updated.');
    }

    public function show(Expense $expense): View
    {
        $this->authorizeTenant($expense);
        $expense->load(['vendor', 'project', 'paymentAccount', 'creator', 'updater', 'approver', 'lines.category', 'lines.client', 'lines.project', 'lines.department', 'attachments.uploader']);

        return view('admin.finance.expenses.show', compact('expense'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $expense->update(['deleted_by' => auth()->id()]);
        $expense->delete();

        ActivityLog::record('deleted', "Deleted expense {$expense->expense_number}".(($data['reason'] ?? null) ? " — {$data['reason']}" : '.'), $expense);

        return redirect()->route('admin.finance.expenses.index')->with('status', 'Expense removed.');
    }

    /** Draft → Pending Approval (or straight to Approved when the tenant doesn't require approval). */
    public function submit(Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->status === 'draft', 422, 'Only a draft expense can be submitted.');

        $settings = FinanceSettings::forTenant($this->tenant());

        if ($settings->expense_approval_required) {
            $expense->update(['status' => 'pending_approval']);
            $approvers = User::where('tenant_id', $expense->tenant_id)->whereIn('role', ['owner', 'admin'])->get();
            SafeNotifier::send($approvers, new ExpenseSubmittedForApproval($expense));
            ActivityLog::record('submitted', "Submitted expense {$expense->expense_number} for approval.", $expense);

            return back()->with('status', 'Submitted for approval.');
        }

        $expense->approve(auth()->user());
        LedgerPoster::expenseApproved($expense, $settings);
        ActivityLog::record('approved', "Expense {$expense->expense_number} auto-approved (no approval required).", $expense);

        return back()->with('status', 'Expense approved.');
    }

    public function approve(Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->status === 'pending_approval', 422, 'This expense has nothing awaiting approval.');

        $expense->approve(auth()->user());
        LedgerPoster::expenseApproved($expense, FinanceSettings::forTenant($this->tenant()));
        ActivityLog::record('approved', "Approved expense {$expense->expense_number}.", $expense);

        SafeNotifier::notify($expense->creator, new ExpenseApproved($expense));

        return back()->with('status', 'Expense approved.');
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->status === 'pending_approval', 422, 'This expense has nothing awaiting approval.');

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $expense->reject(auth()->user(), $data['reason'] ?? null);
        ActivityLog::record('rejected', "Rejected expense {$expense->expense_number} — sent back to draft.", $expense);

        SafeNotifier::notify($expense->creator, new ExpenseRejected($expense));

        return back()->with('status', 'Expense rejected — sent back to draft.');
    }

    public function markPaid(Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($expense->status === 'approved', 422, 'Only an approved expense can be marked paid.');

        $expense->markPaid();
        ActivityLog::record('paid', "Marked expense {$expense->expense_number} as paid.", $expense);

        return back()->with('status', 'Expense marked as paid.');
    }

    public function cancel(Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        abort_if($expense->status === 'paid', 422, 'A paid expense can\'t be cancelled.');

        $expense->cancel();
        ActivityLog::record('cancelled', "Cancelled expense {$expense->expense_number}.", $expense);

        return back()->with('status', 'Expense cancelled.');
    }

    public function duplicate(Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($expense);
        $tenant = $this->tenant();

        $copy = DB::transaction(function () use ($tenant, $expense) {
            $lockedSettings = FinanceSettings::where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();

            return $expense->duplicate(auth()->user(), $lockedSettings->consumeNextExpenseNumber());
        });
        ActivityLog::record('created', "Duplicated expense {$expense->expense_number} as {$copy->expense_number}.", $copy);

        return redirect()->route('admin.finance.expenses.edit', $copy)->with('status', 'Duplicated as '.$copy->expense_number.' — review and save.');
    }

    public function downloadAttachment(Expense $expense, ExpenseAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeTenant($expense);
        abort_unless($attachment->expense_id === $expense->id, 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    private function filtered(Request $request, int $tenantId): Builder
    {
        return Expense::where('tenant_id', $tenantId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($q2) => $q2->where('expense_number', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('vendor', fn ($q3) => $q3->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
            ->when($request->filled('payment_account_id'), fn ($q) => $q->where('payment_account_id', $request->payment_account_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->to));
    }

    private function formOptions(int $tenantId, FinanceSettings $settings): array
    {
        return [
            'vendors' => Vendor::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'categories' => ExpenseCategory::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('tenant_id', $tenantId)->where('status', '!=', 'archived')->orderBy('name')->get(),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'clients' => Client::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'paymentAccounts' => $this->paymentAccounts($tenantId),
            'settings' => $settings,
        ];
    }

    private function paymentAccounts(int $tenantId)
    {
        return ChartOfAccount::where('tenant_id', $tenantId)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
    }

    private function storeAttachments(Request $request, Expense $expense): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("expense-attachments/{$expense->tenant_id}", 'local');

            $expense->attachments()->create([
                'uploaded_by' => auth()->id(),
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'type' => 'receipt',
            ]);
        }
    }

    private function assertWithinMax(array $lines, array $data, FinanceSettings $settings): void
    {
        if ($settings->expense_max_amount === null) {
            return;
        }

        $subtotal = array_sum(array_column($lines, 'amount'));
        $total = $subtotal + ($data['tax_amount'] ?? 0) - ($data['discount_amount'] ?? 0);

        if ($total > (float) $settings->expense_max_amount) {
            throw ValidationException::withMessages([
                'lines' => 'This exceeds the maximum expense amount allowed ('.$settings->formatMoney($settings->expense_max_amount).').',
            ]);
        }
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Expense $expense): void
    {
        abort_unless($expense->tenant_id === $this->tenant()->id, 404);
    }

    private function validatedHeader(Request $request, int $tenantId): array
    {
        $data = $request->validate([
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')->where('tenant_id', $tenantId)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'expense_date' => ['required', 'date'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'payment_account_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where('tenant_id', $tenantId)],
            'payment_method' => ['nullable', 'string', Rule::in(Expense::PAYMENT_METHODS)],
            'payment_status' => ['required', 'string', Rule::in(Expense::PAYMENT_STATUSES)],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_interval' => ['nullable', 'string', Rule::in(Expense::RECURRENCE_INTERVALS)],
        ]);

        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['discount_amount'] = $data['discount_amount'] ?? 0;
        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['recurrence_interval'] = $data['is_recurring'] ? ($data['recurrence_interval'] ?? 'monthly') : null;
        $data['next_occurrence_date'] = $data['is_recurring'] ? now()->addMonth()->toDateString() : null;

        return $data;
    }

    private function validatedLines(Request $request, int $tenantId): array
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId)],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('tenant_id', $tenantId)],
            'lines.*.project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'lines.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'lines.*.class_tag' => ['nullable', 'string', 'max:100'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return $data['lines'];
    }
}
