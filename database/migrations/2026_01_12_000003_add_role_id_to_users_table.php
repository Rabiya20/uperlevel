<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nullable and separate from the existing "role" enum column on
    // purpose: "role" (owner/admin/manager/employee) still decides which
    // portal a user lands in and the coarse access boundary that already
    // works today. This "role_id" is an optional, finer-grained layer on
    // top — a user with no custom role assigned behaves exactly as before.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
