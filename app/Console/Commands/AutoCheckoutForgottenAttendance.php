<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Minimal scheduled automation — see app/Console/Kernel.php for the hourly
 * schedule. Runs synchronously (no queue worker in this environment): a
 * per-tenant scan of open sessions is cheap enough to run inline.
 */
class AutoCheckoutForgottenAttendance extends Command
{
    protected $signature = 'attendance:auto-checkout';

    protected $description = 'Check out any employee still open past their shift end time, using the shift\'s end time as the checkout.';

    public function handle(): int
    {
        $total = 0;

        Tenant::each(function (Tenant $tenant) use (&$total) {
            Attendance::where('tenant_id', $tenant->id)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->whereNotNull('shift_id')
                ->with('shift')
                ->get()
                ->each(function (Attendance $attendance) use (&$total) {
                    $shiftEnd = $attendance->shift->endDateTimeFor($attendance->work_date);

                    if (now()->greaterThanOrEqualTo($shiftEnd)) {
                        $attendance->update(['check_out' => $shiftEnd]);
                        $total++;
                    }
                });
        });

        $this->info("Auto-checked-out {$total} attendance record(s) past their shift end time.");

        return self::SUCCESS;
    }
}
