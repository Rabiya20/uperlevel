<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();
        Designation::query()->delete();

        // Platform-level user — no tenant_id, so no tenant-scoped designation either.
        User::create([
            'name' => 'Rabia Mushtaq',
            'email' => 'rabiamushtaq@uperlevel.tech',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $metagenix = Tenant::where('slug', 'metagenix')->first();
        $northline = Tenant::where('slug', 'northline')->first();

        $designation = fn (Tenant $tenant, string $name) => Designation::create(['tenant_id' => $tenant->id, 'name' => $name]);

        User::create([
            'tenant_id' => $metagenix->id,
            'name' => 'Ahmed Raza',
            'email' => 'owner@metagenix.co',
            'password' => Hash::make('ownerpassword'),
            'role' => User::ROLE_OWNER,
            'designation_id' => $designation($metagenix, 'Founder & CEO')->id,
        ]);

        User::create([
            'tenant_id' => $metagenix->id,
            'name' => 'Bilal Farooq',
            'email' => 'admin@metagenix.co',
            'password' => Hash::make('adminpassword'),
            'role' => User::ROLE_ADMIN,
            'designation_id' => $designation($metagenix, 'Operations Admin')->id,
        ]);

        User::create([
            'tenant_id' => $metagenix->id,
            'name' => 'Hina Malik',
            'email' => 'manager@metagenix.co',
            'password' => Hash::make('managerpassword'),
            'role' => User::ROLE_MANAGER,
            'designation_id' => $designation($metagenix, 'Creative Manager / HOD')->id,
        ]);

        User::create([
            'tenant_id' => $metagenix->id,
            'name' => 'Sana Khan',
            'email' => 'employee@metagenix.co',
            'password' => Hash::make('employeepassword'),
            'role' => User::ROLE_EMPLOYEE,
            'designation_id' => $designation($metagenix, 'Video Editor')->id,
        ]);

        User::create([
            'tenant_id' => $northline->id,
            'name' => 'Imran Sheikh',
            'email' => 'owner@northline.co',
            'password' => Hash::make('ownerpassword'),
            'role' => User::ROLE_OWNER,
            'designation_id' => $designation($northline, 'Founder & CEO')->id,
        ]);
    }
}
