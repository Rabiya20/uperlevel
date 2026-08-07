<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(): View
    {
        $tenant = $this->tenant();

        $entries = JournalEntry::where('tenant_id', $tenant->id)
            ->withCount('lines')
            ->with('creator')
            ->latest('entry_date')
            ->latest('id')
            ->paginate(25);

        return view('admin.finance.ledger.entries.index', compact('entries'));
    }

    public function create(): View
    {
        $accounts = ChartOfAccount::where('tenant_id', $this->tenant()->id)->where('is_active', true)->orderBy('type')->orderBy('code')->get();

        return view('admin.finance.ledger.entries.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $accountIds = ChartOfAccount::where('tenant_id', $tenant->id)->pluck('id');

        $validator = Validator::make($request->all(), [
            'entry_date' => ['required', 'date'],
            'memo' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', Rule::in($accountIds)],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('lines', []) as $i => $line) {
                if ((float) ($line['debit'] ?? 0) > 0 && (float) ($line['credit'] ?? 0) > 0) {
                    $validator->errors()->add("lines.{$i}.debit", 'A line can\'t be both a debit and a credit — enter one or the other.');
                }
            }
        });

        $data = $validator->validate();

        $lines = collect($data['lines'])
            ->map(fn ($line) => [
                'chart_of_account_id' => $line['chart_of_account_id'],
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
            ])
            ->filter(fn ($line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values()
            ->all();

        try {
            $entry = JournalEntry::post(
                $tenant->id,
                \Illuminate\Support\Carbon::parse($data['entry_date']),
                $data['memo'],
                $lines,
                null,
                auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('admin.finance.ledger.entries.show', $entry)->with('status', 'Journal entry posted.');
    }

    public function show(JournalEntry $journalEntry): View
    {
        $this->authorizeTenant($journalEntry);
        $journalEntry->load(['lines.chartOfAccount', 'creator', 'source']);

        return view('admin.finance.ledger.entries.show', ['entry' => $journalEntry]);
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }

    private function authorizeTenant(JournalEntry $entry): void
    {
        abort_unless($entry->tenant_id === $this->tenant()->id, 404);
    }
}
