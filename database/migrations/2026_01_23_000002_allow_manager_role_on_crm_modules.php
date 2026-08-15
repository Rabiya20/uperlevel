<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // CRM was previously owner/admin-only at the route level (unlike HR/
    // Projects/Company, which already allow Manager as a base role and rely
    // on the custom-role system to narrow things down per user). Updating
    // roles in place — not deleting and re-seeding Module rows — preserves
    // existing module_id references from role_permissions (cascadeOnDelete
    // would otherwise wipe every tenant's saved custom-role permissions).
    private const KEYS = [
        'crm', 'crm-overview', 'crm-leads', 'crm-followups',
        'crm-approvals', 'crm-imports', 'crm-reports', 'crm-setup',
    ];

    public function up(): void
    {
        DB::table('modules')
            ->where('portal', 'admin')
            ->whereIn('key', self::KEYS)
            ->update(['roles' => 'owner,admin,manager']);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('portal', 'admin')
            ->whereIn('key', self::KEYS)
            ->update(['roles' => 'owner,admin']);
    }
};
