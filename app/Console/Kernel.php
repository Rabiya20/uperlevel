<?php

namespace App\Console;

use App\Console\Commands\AutoCheckoutForgottenAttendance;
use App\Console\Commands\ProcessFinanceReminders;
use App\Console\Commands\PruneOldScreenCaptures;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Nothing in this environment actually calls `schedule:run` on a timer —
     * production needs a single crontab entry:
     *   * * * * * php artisan schedule:run >> /dev/null 2>&1
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ProcessFinanceReminders::class)->daily();
        $schedule->command(AutoCheckoutForgottenAttendance::class)->hourly();
        $schedule->command(PruneOldScreenCaptures::class)->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
