<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\FinanceSettings;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminder;
use App\Support\Notifications\SafeNotifier;
use Illuminate\Console\Command;

/**
 * Minimal scheduled automation — see app/Console/Kernel.php for the daily
 * schedule. Runs synchronously (no queue worker in this environment): both
 * steps below are cheap per-tenant scans, fine to run inline once a day.
 */
class ProcessFinanceReminders extends Command
{
    protected $signature = 'finance:process-reminders';

    protected $description = 'Notify owners/admins about newly-overdue invoices and generate the next occurrence of due recurring expenses.';

    public function handle(): int
    {
        $overdueCount = 0;
        $recurringCount = 0;

        Tenant::each(function (Tenant $tenant) use (&$overdueCount, &$recurringCount) {
            $settings = FinanceSettings::forTenant($tenant);
            $recipients = User::where('tenant_id', $tenant->id)->whereIn('role', ['owner', 'admin'])->get();

            $overdueInvoices = Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'sent')
                ->whereNull('overdue_notified_at')
                ->whereDate('due_date', '<=', now()->subDays($settings->invoice_overdue_grace_days))
                ->with('client')
                ->get();

            foreach ($overdueInvoices as $invoice) {
                if ($recipients->isNotEmpty()) {
                    SafeNotifier::send($recipients, new InvoiceOverdueReminder($invoice));
                }
                $invoice->update(['overdue_notified_at' => now()]);
                $overdueCount++;
            }

            $dueRecurring = Expense::where('tenant_id', $tenant->id)
                ->where('is_recurring', true)
                ->whereDate('next_occurrence_date', '<=', now())
                ->get();

            foreach ($dueRecurring as $expense) {
                $expense->generateNextOccurrence();
                $recurringCount++;
            }
        });

        $this->info("Notified {$overdueCount} overdue invoice(s), generated {$recurringCount} recurring expense occurrence(s).");

        return self::SUCCESS;
    }
}
