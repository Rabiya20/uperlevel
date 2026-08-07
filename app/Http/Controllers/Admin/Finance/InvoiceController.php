<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Support\Ledger\LedgerPoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with('client')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('issue_date')
            ->paginate(25)
            ->withQueryString();

        $settings = FinanceSettings::forTenant($tenant);

        return view('admin.finance.invoices.index', compact('invoices', 'settings'));
    }

    public function create(Request $request): View
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $clients = Client::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $selectedClientId = $request->integer('client_id') ?: null;

        return view('admin.finance.invoices.create', [
            'settings' => $settings,
            'clients' => $clients,
            'selectedClientId' => $selectedClientId,
            'nextNumber' => $settings->nextInvoiceNumberPreview(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'apply_tax' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $client = Client::where('tenant_id', $tenant->id)->findOrFail($data['client_id']);

        $taxPercentage = $request->boolean('apply_tax') ? (float) $settings->tax_percentage : 0;

        if ($taxPercentage > 0 && $settings->tax_inclusive_pricing) {
            // The entered amount already includes tax — back the tax portion out of it
            // rather than adding it on top, so the total stays equal to what was typed.
            $taxAmount = round($data['subtotal'] - ($data['subtotal'] / (1 + $taxPercentage / 100)), 2);
            $total = round((float) $data['subtotal'], 2);
        } else {
            $taxAmount = round($data['subtotal'] * $taxPercentage / 100, 2);
            $total = round($data['subtotal'] + $taxAmount, 2);
        }

        // consumeNextInvoiceNumber() reads-then-increments the counter — two
        // concurrent submits (a double-click, or a slow request plus a
        // retry) can both read the same number before either commits.
        // Locking the settings row for the duration of the transaction
        // forces the second request to wait and see the already-advanced
        // counter, instead of racing to insert the same invoice_number.
        $invoice = DB::transaction(function () use ($tenant, $client, $data, $taxPercentage, $taxAmount, $total) {
            $lockedSettings = FinanceSettings::where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();

            return Invoice::create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'created_by' => auth()->id(),
                'invoice_number' => $lockedSettings->consumeNextInvoiceNumber(),
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'status' => 'draft',
                'subtotal' => $data['subtotal'],
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        LedgerPoster::invoiceCreated($invoice, $settings);

        return redirect()->route('admin.finance.invoices.show', $invoice)->with('status', 'Invoice '.$invoice->invoice_number.' created as a draft.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeTenant($invoice);
        $invoice->load('client', 'creator', 'payments.receiver');

        $settings = FinanceSettings::forTenant($this->tenant());

        return view('admin.finance.invoices.show', compact('invoice', 'settings'));
    }

    public function markSent(Invoice $invoice): RedirectResponse
    {
        $this->authorizeTenant($invoice);
        abort_unless($invoice->status === 'draft', 422, 'Only a draft invoice can be sent.');

        $invoice->update(['status' => 'sent']);

        return back()->with('status', 'Invoice marked as sent.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorizeTenant($invoice);
        abort_unless($invoice->status !== 'paid', 422, 'A paid invoice can\'t be cancelled.');

        $invoice->update(['status' => 'cancelled']);

        return back()->with('status', 'Invoice cancelled.');
    }

    private function tenant()
    {
        $tenant = App::make('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(Invoice $invoice): void
    {
        abort_unless($invoice->tenant_id === $this->tenant()->id, 404);
    }
}
