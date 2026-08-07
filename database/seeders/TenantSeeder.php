<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->delete();

        Tenant::create(['name' => 'Metagenix', 'slug' => 'metagenix', 'logo_initials' => 'MX', 'plan' => 'growth', 'status' => 'active']);
        Tenant::create(['name' => 'Northline Studios', 'slug' => 'northline', 'logo_initials' => 'NS', 'plan' => 'scale', 'status' => 'active']);
        Tenant::create(['name' => 'Bytecraft Dev', 'slug' => 'bytecraft', 'logo_initials' => 'BC', 'plan' => 'trial', 'status' => 'trial']);
    }
}
