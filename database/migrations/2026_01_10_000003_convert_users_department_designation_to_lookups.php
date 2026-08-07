<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Both string columns are dropped in favor of department_id/designation_id
    // lookups (managed from HR Setup). This is a dev/demo dataset — existing
    // rows are reseeded via UserSeeder rather than data-migrated in place.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['department', 'designation']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('phone')->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->after('role')->constrained('designations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('designation_id');
            $table->string('department')->nullable()->after('phone');
            $table->string('designation')->nullable()->after('role');
        });
    }
};
