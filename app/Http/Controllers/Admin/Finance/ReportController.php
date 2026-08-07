<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Models\JournalEntryLine;
use App\Models\Vendor;
use App\Support\Reports\ExportsTabularReports;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsTabularReports;

    /** Reports hub — four report types, each with its own filters and PDF/Excel/Print export. */
    public function index(): View
    {
        return view('admin.finance.reports.index');
    }

    // ---- Profit & Loss ----------------------------------------------------

    public function profitLoss(Request $request): View
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->profitLossData($start, $end);

        return view('admin.finance.reports.profit-loss', array_merge($data, ['start' => $start, 'end' => $end]));
    }

    public function profitLossExport(Request $request, string $format): Response
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->profitLossData($start, $end);

        return $this->export(
            'Profit & Loss',
            $start->format('M j, Y').' – '.$end->format('M j, Y'),
            $data['headers'], $data['rows'], $format
        );
    }

    private function profitLossData(Carbon $start, Carbon $end): array
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $lines = JournalEntryLine::whereHas('journalEntry', fn ($q) => $q->where('tenant_id', $tenant->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()]))
            ->with('chartOfAccount')
            ->get()
            ->filter(fn (JournalEntryLine $l) => $l->chartOfAccount && in_array($l->chartOfAccount->type, ['income', 'expense'], true));

        $byAccount = $lines->groupBy('chart_of_account_id');

        $headers = ['Account', 'Amount'];
        $rows = [];
        $totalIncome = 0;
        $totalExpense = 0;

        $incomeAccounts = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'income')->orderBy('code')->get();
        foreach ($incomeAccounts as $account) {
            $accountLines = $byAccount->get($account->id, collect());
            $movement = $accountLines->sum(fn (JournalEntryLine $l) => $account->lineMovement($l));
            if ($movement != 0) {
                $rows[] = [$account->code.' — '.$account->name, $settings->formatMoney($movement)];
                $totalIncome += $movement;
            }
        }
        $rows[] = ['Total Income', $settings->formatMoney($totalIncome)];
        $rows[] = ['', ''];

        $expenseAccounts = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'expense')->orderBy('code')->get();
        foreach ($expenseAccounts as $account) {
            $accountLines = $byAccount->get($account->id, collect());
            $movement = $accountLines->sum(fn (JournalEntryLine $l) => $account->lineMovement($l));
            if ($movement != 0) {
                $rows[] = [$account->code.' — '.$account->name, $settings->formatMoney($movement)];
                $totalExpense += $movement;
            }
        }
        $rows[] = ['Total Expenses', $settings->formatMoney($totalExpense)];
        $rows[] = ['', ''];
        $rows[] = ['Net Profit / (Loss)', $settings->formatMoney($totalIncome - $totalExpense)];

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Expense Report -----------------------------------------------------

    public function expenses(Request $request): View
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->expenseReportData($request, $start, $end);

        return view('admin.finance.reports.expenses', array_merge($data, [
            'start' => $start, 'end' => $end,
            'vendors' => Vendor::where('tenant_id', $this->tenant()->id)->orderBy('name')->get(),
        ]));
    }

    public function expensesExport(Request $request, string $format): Response
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->expenseReportData($request, $start, $end);

        return $this->export(
            'Expense Report',
            $start->format('M j, Y').' – '.$end->format('M j, Y'),
            $data['headers'], $data['rows'], $format
        );
    }

    private function expenseReportData(Request $request, Carbon $start, Carbon $end): array
    {
        $tenant = $this->tenant();

        $expenses = Expense::where('tenant_id', $tenant->id)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
            ->with('vendor')
            ->orderBy('expense_date')
            ->get();

        $headers = ['Date', 'Expense No.', 'Vendor', 'Subtotal', 'Tax', 'Total', 'Status'];
        $rows = $expenses->map(fn (Expense $e) => [
            $e->expense_date->format('j M Y'),
            $e->expense_number,
            $e->vendor->name ?? '—',
            number_format((float) $e->subtotal, 2),
            number_format((float) $e->tax_amount, 2),
            number_format((float) $e->total_amount, 2),
            ucfirst(str_replace('_', ' ', $e->status)),
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Revenue / AR Aging --------------------------------------------------

    public function revenue(): View
    {
        $data = $this->revenueData();

        return view('admin.finance.reports.revenue', $data);
    }

    public function revenueExport(string $format): Response
    {
        $data = $this->revenueData();

        return $this->export('Revenue & AR Aging', 'All non-draft invoices', $data['headers'], $data['rows'], $format);
    }

    private function revenueData(): array
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'draft')
            ->with('client')
            ->orderBy('due_date')
            ->get();

        $headers = ['Invoice #', 'Client', 'Due Date', 'Total', 'Paid', 'Balance Due', 'Days Overdue', 'Aging'];
        $rows = $invoices->map(function (Invoice $invoice) use ($settings) {
            $daysOverdue = $invoice->isOverdue($settings->invoice_overdue_grace_days)
                ? now()->diffInDays($invoice->due_date)
                : 0;

            return [
                $invoice->invoice_number,
                $invoice->client->name ?? '—',
                $invoice->due_date->format('j M Y'),
                number_format((float) $invoice->total, 2),
                number_format($invoice->amountPaid(), 2),
                number_format($invoice->balanceDue(), 2),
                $daysOverdue,
                $this->agingBucket($daysOverdue),
            ];
        })->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function agingBucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'Current',
            $daysOverdue <= 30 => '1-30 days',
            $daysOverdue <= 60 => '31-60 days',
            $daysOverdue <= 90 => '61-90 days',
            default => '90+ days',
        };
    }

    // ---- Trial Balance --------------------------------------------------------

    public function trialBalance(): View
    {
        $data = $this->trialBalanceData();

        return view('admin.finance.reports.trial-balance', $data);
    }

    public function trialBalanceExport(string $format): Response
    {
        $data = $this->trialBalanceData();

        return $this->export('Trial Balance', 'As of '.now()->format('M j, Y'), $data['headers'], $data['rows'], $format);
    }

    private function trialBalanceData(): array
    {
        $tenant = $this->tenant();

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)->orderBy('type')->orderBy('code')->get();

        $headers = ['Code', 'Account', 'Type', 'Debit', 'Credit'];
        $totalDebit = 0;
        $totalCredit = 0;

        $rows = $accounts->map(function (ChartOfAccount $account) use (&$totalDebit, &$totalCredit) {
            $balance = $account->currentBalance();
            // A balance on the "wrong side" (e.g. a debit-normal account
            // that's gone credit) still has to show up somewhere — flip it
            // into the opposite column rather than dropping it, or the
            // debit/credit totals below would silently stop matching.
            $debit = $account->isCreditNormal() ? max(0, -$balance) : max(0, $balance);
            $credit = $account->isCreditNormal() ? max(0, $balance) : max(0, -$balance);
            $totalDebit += $debit;
            $totalCredit += $credit;

            return [$account->code, $account->name, ucfirst($account->type), number_format($debit, 2), number_format($credit, 2)];
        })->all();

        $rows[] = ['', 'Total', '', number_format($totalDebit, 2), number_format($totalCredit, 2)];

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Shared plumbing -----------------------------------------------------

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
