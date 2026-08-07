<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Ledger\LedgerPoster;
use App\Support\Reports\ExportsTabularReports;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ExportsTabularReports;

    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $payments = $this->filtered($request, $tenant->id)
            ->with(['invoice', 'client'])
            ->latest('payment_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.payments.index', [
            'payments' => $payments,
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('name')->get(),
            'settings' => FinanceSettings::forTenant($tenant),
        ]);
    }

    public function exportList(Request $request, string $format): Response
    {
        $tenant = $this->tenant();

        $payments = $this->filtered($request, $tenant->id)->with(['invoice', 'client'])->orderBy('payment_date')->get();

        $headers = ['Date', 'Invoice #', 'Client', 'Amount', 'Method', 'Reference'];
        $rows = $payments->map(fn (Payment $p) => [
            $p->payment_date->format('j M Y'),
            $p->invoice->invoice_number ?? '—',
            $p->client->name ?? '—',
            number_format((float) $p->amount, 2),
            ucfirst(str_replace('_', ' ', $p->payment_method)),
            $p->reference_number ?? '—',
        ])->all();

        return $this->export('Payments', 'All recorded payments', $headers, $rows, $format);
    }

    public function create(Request $request): View
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $openInvoices = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['draft', 'sent'])
            ->with('client')
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $i) => $i->balanceDue() > 0)
            ->values();

        $selectedInvoiceId = $request->integer('invoice_id') ?: null;
        $selectedInvoice = $selectedInvoiceId ? $openInvoices->firstWhere('id', $selectedInvoiceId) : null;

        return view('admin.finance.payments.create', [
            'openInvoices' => $openInvoices,
            'selectedInvoice' => $selectedInvoice,
            'methods' => $settings->accepted_payment_methods ?: Payment::METHODS,
            'depositAccounts' => $this->depositAccounts($tenant->id),
            'settings' => $settings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $data = $request->validate([
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenant->id)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(Payment::METHODS)],
            'deposit_account_id' => ['nullable', 'integer', Rule::exists('chart_of_accounts', 'id')->where('tenant_id', $tenant->id)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($data['invoice_id']);
        $balanceDue = $invoice->balanceDue();

        if ($balanceDue <= 0) {
            return back()->withInput()->withErrors(['amount' => 'This invoice is already fully paid.']);
        }

        if ((float) $data['amount'] > $balanceDue) {
            return back()->withInput()->withErrors(['amount' => 'This exceeds the remaining balance due ('.$settings->formatMoney($balanceDue).').']);
        }

        $payment = DB::transaction(function () use ($tenant, $invoice, $data) {
            $payment = Payment::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'client_id' => $invoice->client_id,
                'received_by' => auth()->id(),
            ]);

            if ($invoice->balanceDue() <= 0) {
                $invoice->update(['status' => 'paid']);
            }

            return $payment;
        });

        LedgerPoster::paymentReceived($payment, $settings);

        ActivityLog::record('payment_recorded', "Recorded payment of {$settings->formatMoney($payment->amount)} for invoice {$invoice->invoice_number}.", $invoice, ['payment_id' => $payment->id]);

        return redirect()->route('admin.finance.invoices.show', $invoice)->with('status', 'Payment recorded.');
    }

    private function filtered(Request $request, int $tenantId): Builder
    {
        return Payment::where('tenant_id', $tenantId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($q2) => $q2->where('reference_number', 'like', $term)
                    ->orWhereHas('invoice', fn ($q3) => $q3->where('invoice_number', 'like', $term))
                    ->orWhereHas('client', fn ($q3) => $q3->where('name', 'like', $term)));
            })
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->to));
    }

    private function depositAccounts(int $tenantId)
    {
        return ChartOfAccount::where('tenant_id', $tenantId)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
