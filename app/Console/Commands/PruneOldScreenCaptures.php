<?php

namespace App\Console\Commands;

use App\Models\ScreenCapture;
use App\Models\ScreenCaptureSettings;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Minimal scheduled automation — see app/Console/Kernel.php for the daily
 * schedule. Runs synchronously (no queue worker in this environment).
 */
class PruneOldScreenCaptures extends Command
{
    protected $signature = 'screen-captures:prune';

    protected $description = 'Delete screenshots older than each tenant\'s configured retention period. A null retention period means keep forever.';

    public function handle(): int
    {
        $total = 0;

        Tenant::each(function (Tenant $tenant) use (&$total) {
            $settings = ScreenCaptureSettings::forTenant($tenant);

            if (! $settings->retention_days) {
                return;
            }

            $cutoff = now()->subDays($settings->retention_days);

            ScreenCapture::where('tenant_id', $tenant->id)
                ->where('captured_at', '<', $cutoff)
                ->get()
                ->each(function (ScreenCapture $capture) use (&$total) {
                    Storage::disk('local')->delete($capture->file_path);
                    $capture->delete();
                    $total++;
                });
        });

        $this->info("Pruned {$total} screenshot(s) past their tenant's retention period.");

        return self::SUCCESS;
    }
}
