<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // null tenant_id = platform-level user (superadmin)
            $table->foreignId('tenant_id')->nullable()->after('id')
                ->constrained('tenants')->nullOnDelete();

            // superadmin | owner | admin | manager | employee
            $table->string('role')->default('employee')->after('tenant_id');

            $table->string('designation')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('designation');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'designation', 'avatar']);
        });
    }
};
