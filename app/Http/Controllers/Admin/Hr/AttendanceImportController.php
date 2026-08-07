<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceImportController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        return view('admin.hr.attendance.import', [
            'employees' => User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
            'timeOptions' => $this->timeOptions(),
            'selectedUserId' => $request->integer('user_id') ?: null,
            'selectedDate' => $request->filled('date') ? $request->date : null,
            'maxDate' => now()->subDay()->toDateString(),
        ]);
    }

    public function manualStore(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'work_date' => ['required', 'date', 'before:today'],
            'status' => ['required', 'string', 'in:present,absent'],
            'check_in_time' => ['required_if:status,present', 'nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
        ]);

        $user = User::findOrFail($data['user_id']);
        abort_unless($user->tenant_id === $tenant->id, 404);

        Attendance::upsertManualEntry(
            $user,
            Carbon::parse($data['work_date']),
            $data['status'],
            $data['check_in_time'] ?? null,
            $data['check_out_time'] ?? null,
            $request->user()
        );

        return back()->with('status', $data['work_date'].' attendance saved for '.$user->name.'.');
    }

    public function template(): StreamedResponse
    {
        $writer = new Xlsx(Attendance::sampleImportSpreadsheet());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'attendance-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importStore(Request $request): View
    {
        $tenant = $this->tenant();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $result = Attendance::importFromSpreadsheet($request->file('file'), $tenant, $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return view('admin.hr.attendance.import-result', $result);
    }

    /** 15-minute increments across a full day, value in 24h "H:i" for parsing, label in 12h for display. */
    private function timeOptions(): array
    {
        $options = [];
        for ($minutes = 0; $minutes < 24 * 60; $minutes += 15) {
            $time = Carbon::createFromTime(0, 0)->addMinutes($minutes);
            $options[] = ['value' => $time->format('H:i'), 'label' => $time->format('g:i A')];
        }

        return $options;
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
