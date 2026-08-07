<?php

namespace App\Providers;

use App\Models\ChartOfAccount;
use App\Models\CrmSettings;
use App\Models\Department;
use App\Models\Designation;
use App\Models\FinanceSettings;
use App\Models\Holiday;
use App\Models\HrSettings;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Milestone;
use App\Models\PayrollComponent;
use App\Models\Project;
use App\Models\Role;
use App\Models\Shift;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatic system-wide audit trail for the app's admin-configurable
        // CRUD/settings models — feeds Company > Log History. Deliberately
        // excludes Attendance (routine check-in/out noise) and User (has its
        // own touch points elsewhere); Lead has its own richer per-lead log
        // that also forwards into ActivityLog via Lead::logActivity().
        foreach ([
            Holiday::class,
            Shift::class,
            HrSettings::class,
            LeaveType::class,
            PayrollComponent::class,
            Department::class,
            Designation::class,
            ChartOfAccount::class,
            FinanceSettings::class,
            CrmSettings::class,
            Role::class,
            LeaveRequest::class,
            Invoice::class,
            Project::class,
            Milestone::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
