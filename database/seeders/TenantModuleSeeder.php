<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Every top-level admin-portal + employee-portal module,
        // enabled by default for the demo tenants.
        $adminModuleIds = Module::forPortal('admin')->topLevel()->pluck('id');
        $employeeModuleIds = Module::forPortal('employee')->topLevel()->pluck('id');
        $allIds = $adminModuleIds->merge($employeeModuleIds);

        Tenant::all()->each(function (Tenant $tenant) use ($allIds) {
            foreach ($allIds as $moduleId) {
                $tenant->modules()->syncWithoutDetaching([$moduleId => ['enabled' => true]]);
            }
        });

        // Demonstrate the per-tenant module toggle: Bytecraft (trial plan)
        // does not have Finance or CRM switched on yet.
        $bytecraft = Tenant::where('slug', 'bytecraft')->first();
        if ($bytecraft) {
            $financeId = Module::forPortal('admin')->where('key', 'finance')->value('id');
            $crmId = Module::forPortal('admin')->where('key', 'crm')->value('id');
            $bytecraft->modules()->updateExistingPivot($financeId, ['enabled' => false]);
            $bytecraft->modules()->updateExistingPivot($crmId, ['enabled' => false]);
        }
    }
}
