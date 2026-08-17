<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $hr = Module::where('portal', 'admin')->where('key', 'hr')->first();

        if (! $hr) {
            return;
        }

        Module::firstOrCreate(
            ['portal' => 'admin', 'key' => 'hr-salary'],
            [
                'parent_id' => $hr->id,
                'name' => 'Salary',
                'icon' => null,
                'route_name' => 'admin.hr.salary.index',
                'roles' => 'owner,admin,manager',
                'sort_order' => 10,
            ]
        );
    }

    public function down(): void
    {
        Module::where('portal', 'admin')->where('key', 'hr-salary')->delete();
    }
};
