<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeePayrollComponent;
use App\Models\HrSettings;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\User;
use App\Support\Reports\ExportsTabularReports;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsTabularReports;

    /** Reports hub — five report types, each with its own filters and PDF/Excel/Print export. */
    public function index(): View
    {
        return view('admin.hr.reports.index');
    }

    // ---- Attendance ------------------------------------------------------

    public function attendance(Request $request): View
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->attendanceData($start, $end);

        return view('admin.hr.reports.attendance', array_merge($data, ['start' => $start, 'end' => $end]));
    }

    public function attendanceExport(Request $request, string $format): Response
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->attendanceData($start, $end);

        return $this->export(
            'Attendance Report',
            $start->format('M j, Y').' – '.$end->format('M j, Y'),
            $data['headers'], $data['rows'], $format
        );
    }

    private function attendanceData(Carbon $start, Carbon $end): array
    {
        $tenant = $this->tenant();
        $settings = HrSettings::forTenant($tenant);
        $users = User::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $headers = ['Name', 'Role', 'Present', 'Late', 'Absent', 'Leave', 'Worked Hours', 'Overtime Hours'];

        $rows = $users->map(function (User $user) use ($start, $end, $settings) {
            $stats = Attendance::summarize(Attendance::dayStatusesFor($user, $start, $end, $settings), $settings);

            return [
                $user->name, ucfirst($user->role), $stats['present'], $stats['late'],
                $stats['absent'], $stats['leave'], $stats['worked_hours'], $stats['overtime_hours'],
            ];
        })->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Employees ---------------------------------------------------------

    public function employees(): View
    {
        $data = $this->employeeData();

        return view('admin.hr.reports.employees', $data);
    }

    public function employeesExport(string $format): Response
    {
        $data = $this->employeeData();

        return $this->export('Employee Report', 'All employees & managers', $data['headers'], $data['rows'], $format);
    }

    private function employeeData(): array
    {
        $tenant = $this->tenant();

        $users = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['employee', 'manager'])
            ->with(['department', 'designation', 'shift'])
            ->orderBy('name')
            ->get();

        $headers = ['Name', 'Code', 'Email', 'Phone', 'Department', 'Designation', 'Role', 'Type', 'Status', 'Joined', 'Shift'];

        $rows = $users->map(fn (User $u) => [
            $u->name, $u->employee_code ?? '—', $u->email, $u->phone ?? '—',
            $u->department->name ?? '—', $u->designation->name ?? '—', ucfirst($u->role),
            User::EMPLOYMENT_TYPES[$u->employment_type] ?? '—', User::EMPLOYMENT_STATUSES[$u->employment_status] ?? '—',
            optional($u->date_of_joining)->format('j M Y') ?? '—', $u->shift->name ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Payroll Structure (owner/admin only, enforced at the route) ----

    public function payroll(): View
    {
        $data = $this->payrollData();

        return view('admin.hr.reports.payroll', $data);
    }

    public function payrollExport(string $format): Response
    {
        $data = $this->payrollData();

        return $this->export('Payroll Structure Report', 'Current pay component assignments', $data['headers'], $data['rows'], $format);
    }

    private function payrollData(): array
    {
        $tenant = $this->tenant();

        $components = PayrollComponent::where('tenant_id', $tenant->id)->where('is_active', true)
            ->orderBy('type')->orderBy('name')->get();
        $users = User::where('tenant_id', $tenant->id)->whereIn('role', ['employee', 'manager'])->orderBy('name')->get();

        $headers = array_merge(['Name'], $components->pluck('name')->all(), ['Total Payable', 'Total Deductible', 'Net']);

        $rows = $users->map(function (User $user) use ($components) {
            $assigned = EmployeePayrollComponent::where('user_id', $user->id)->pluck('amount', 'payroll_component_id');
            $payable = $deductible = 0;
            $cells = [$user->name];

            foreach ($components as $component) {
                $amount = (float) ($assigned[$component->id] ?? 0);
                $cells[] = number_format($amount, 2);
                if ($component->isEarning()) {
                    $payable += $amount;
                } else {
                    $deductible += $amount;
                }
            }

            $cells[] = number_format($payable, 2);
            $cells[] = number_format($deductible, 2);
            $cells[] = number_format($payable - $deductible, 2);

            return $cells;
        })->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Leave -------------------------------------------------------------

    public function leave(Request $request): View
    {
        $year = (int) ($request->query('year') ?: now()->year);
        $data = $this->leaveData($year);

        return view('admin.hr.reports.leave', array_merge($data, ['year' => $year]));
    }

    public function leaveExport(Request $request, string $format): Response
    {
        $year = (int) ($request->query('year') ?: now()->year);
        $data = $this->leaveData($year);

        return $this->export('Leave Report', 'Calendar year '.$year, $data['headers'], $data['rows'], $format);
    }

    private function leaveData(int $year): array
    {
        $tenant = $this->tenant();

        $leaveTypes = LeaveType::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $users = User::where('tenant_id', $tenant->id)->whereIn('role', ['employee', 'manager'])->orderBy('name')->get();

        $headers = array_merge(['Name'], $leaveTypes->pluck('name')->all());

        $rows = $users->map(function (User $user) use ($leaveTypes, $year) {
            $cells = [$user->name];
            foreach ($leaveTypes as $type) {
                $balance = $type->balanceFor($user, $year);
                $cells[] = $balance['used'].' used / '.$balance['remaining'].' left';
            }

            return $cells;
        })->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---- Performance (attendance-derived — no dedicated review module yet) --

    public function performance(Request $request): View
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->performanceData($start, $end);

        return view('admin.hr.reports.performance', array_merge($data, ['start' => $start, 'end' => $end]));
    }

    public function performanceExport(Request $request, string $format): Response
    {
        [$start, $end] = $this->dateRange($request);
        $data = $this->performanceData($start, $end);

        return $this->export(
            'Performance Report',
            $start->format('M j, Y').' – '.$end->format('M j, Y').' (attendance-based)',
            $data['headers'], $data['rows'], $format
        );
    }

    private function performanceData(Carbon $start, Carbon $end): array
    {
        $tenant = $this->tenant();
        $settings = HrSettings::forTenant($tenant);
        $users = User::where('tenant_id', $tenant->id)->whereIn('role', ['employee', 'manager'])->orderBy('name')->get();

        $workingDays = 0;
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            if ($settings->isWorkingDay($cursor)) {
                $workingDays++;
            }
        }

        $headers = ['Name', 'Present', 'Late', 'Absent', 'Attendance Rate', 'On-Time Rate', 'Avg Hours/Day'];

        $rows = $users->map(function (User $user) use ($start, $end, $settings, $workingDays) {
            $stats = Attendance::summarize(Attendance::dayStatusesFor($user, $start, $end, $settings), $settings);

            $attendanceRate = $workingDays > 0 ? round($stats['present'] / $workingDays * 100, 1) : 0;
            $onTimeRate = $stats['present'] > 0 ? round(($stats['present'] - $stats['late']) / $stats['present'] * 100, 1) : 0;
            $avgHours = $stats['present'] > 0 ? round($stats['worked_hours'] / $stats['present'], 1) : 0;

            return [
                $user->name, $stats['present'], $stats['late'], $stats['absent'],
                $attendanceRate.'%', $onTimeRate.'%', $avgHours,
            ];
        })->all();

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
