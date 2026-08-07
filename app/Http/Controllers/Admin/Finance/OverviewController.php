<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $totalRevenue = (float) Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->sum('total');

        $openInvoices = Invoice::where('tenant_id', $tenant->id)->whereIn('status', ['draft', 'sent'])->get();
        $outstanding = $openInvoices->sum(fn (Invoice $i) => $i->balanceDue());
        $overdueInvoices = $openInvoices->filter(fn (Invoice $i) => $i->isOverdue($settings->invoice_overdue_grace_days));
        $overdueAmount = $overdueInvoices->sum(fn (Invoice $i) => $i->balanceDue());
        $overdueCount = $overdueInvoices->count();

        $monthStart = now()->startOfMonth();
        $expensesThisMonth = (float) Expense::where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->where('expense_date', '>=', $monthStart)
            ->sum('total_amount');
        $revenueThisMonth = (float) Payment::where('tenant_id', $tenant->id)->where('payment_date', '>=', $monthStart)->sum('amount');
        $netThisMonth = $revenueThisMonth - $expensesThisMonth;

        $cashAndBank = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();

        $recentInvoices = Invoice::where('tenant_id', $tenant->id)->with('client')->latest('issue_date')->take(5)->get();
        $recentPayments = Payment::where('tenant_id', $tenant->id)->with(['invoice', 'client'])->latest('payment_date')->take(5)->get();
        $recentExpenses = Expense::where('tenant_id', $tenant->id)->with('vendor')->latest('expense_date')->take(5)->get();

        return view('admin.finance.overview.index', compact(
            'settings', 'totalRevenue', 'outstanding', 'overdueAmount', 'overdueCount',
            'expensesThisMonth', 'revenueThisMonth', 'netThisMonth', 'cashAndBank',
            'recentInvoices', 'recentPayments', 'recentExpenses'
        ));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
