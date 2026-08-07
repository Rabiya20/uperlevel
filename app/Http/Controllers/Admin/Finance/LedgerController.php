<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\FinanceSettings;
use App\Models\JournalEntryLine;
use App\Support\Reports\ExportsTabularReports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LedgerController extends Controller
{
    use ExportsTabularReports;

    public function index(): View
    {
        $tenant = $this->tenant();
        $settings = FinanceSettings::forTenant($tenant);

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('admin.finance.ledger.index', compact('accounts', 'settings'));
    }

    public function show(Request $request, ChartOfAccount $coa): View
    {
        $this->authorizeTenant($coa);

        ['openingBalance' => $openingBalance, 'rows' => $rows, 'closingBalance' => $closingBalance] = $this->buildLedger($request, $coa);

        $settings = FinanceSettings::forTenant($this->tenant());

        return view('admin.finance.ledger.show', [
            'account' => $coa,
            'rows' => $rows,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'settings' => $settings,
        ]);
    }

    public function exportAccount(Request $request, ChartOfAccount $coa, string $format): Response
    {
        $this->authorizeTenant($coa);

        ['rows' => $rows] = $this->buildLedger($request, $coa);

        $headers = ['Date', 'Voucher #', 'Type', 'Payee', 'Description', 'Debit', 'Credit', 'Balance'];
        $tableRows = $rows->map(fn ($row) => [
            $row['line']->journalEntry->entry_date->format('j M Y'),
            $row['line']->journalEntry->reference_number ?? '—',
            $row['line']->journalEntry->transaction_type ? ucfirst($row['line']->journalEntry->transaction_type) : 'Manual',
            $row['line']->journalEntry->payee ?? '—',
            $row['line']->journalEntry->memo,
            $row['line']->debit > 0 ? number_format((float) $row['line']->debit, 2) : '',
            $row['line']->credit > 0 ? number_format((float) $row['line']->credit, 2) : '',
            number_format($row['balance'], 2),
        ])->all();

        return $this->export("{$coa->code} — {$coa->name}", 'Account Ledger', $headers, $tableRows, $format);
    }

    /**
     * @return array{openingBalance: float, rows: \Illuminate\Support\Collection, closingBalance: float}
     */
    private function buildLedger(Request $request, ChartOfAccount $account): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $allLines = JournalEntryLine::where('chart_of_account_id', $account->id)
            ->with('journalEntry')
            ->get()
            ->sortBy(fn (JournalEntryLine $l) => $l->journalEntry->entry_date->format('Y-m-d').'-'.str_pad((string) $l->id, 10, '0', STR_PAD_LEFT));

        $priorLines = $from ? $allLines->filter(fn (JournalEntryLine $l) => $l->journalEntry->entry_date->lt($from)) : collect();

        $inRangeLines = $allLines->filter(function (JournalEntryLine $l) use ($from, $to) {
            $date = $l->journalEntry->entry_date;

            return (! $from || $date->gte($from)) && (! $to || $date->lte($to));
        });

        // Search/type filters narrow only what's displayed — not the prior-balance calculation, which must still reflect real history.
        $displayLines = $inRangeLines->filter(function (JournalEntryLine $l) use ($request) {
            $entry = $l->journalEntry;

            if ($request->filled('q')) {
                $term = mb_strtolower($request->q);
                $haystack = mb_strtolower(($entry->reference_number ?? '').' '.($entry->payee ?? '').' '.$entry->memo);
                if (! str_contains($haystack, $term)) {
                    return false;
                }
            }

            if ($request->filled('transaction_type') && $entry->transaction_type !== $request->transaction_type) {
                return false;
            }

            return true;
        })->values();

        $openingBalance = (float) $account->opening_balance + $priorLines->sum(fn (JournalEntryLine $l) => $account->lineMovement($l));

        $running = $openingBalance;
        $rows = $displayLines->map(function (JournalEntryLine $line) use (&$running, $account) {
            $running += $account->lineMovement($line);

            return ['line' => $line, 'balance' => $running];
        });

        return ['openingBalance' => $openingBalance, 'rows' => $rows, 'closingBalance' => $running];
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(ChartOfAccount $account): void
    {
        abort_unless($account->tenant_id === $this->tenant()->id, 404);
    }
}
