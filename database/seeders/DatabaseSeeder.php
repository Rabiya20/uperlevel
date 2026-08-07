<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
            TenantModuleSeeder::class,
        ]);
    }
}
