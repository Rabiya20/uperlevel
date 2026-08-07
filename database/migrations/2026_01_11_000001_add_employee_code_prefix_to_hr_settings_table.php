<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_settings', function (Blueprint $table) {
            $table->string('employee_code_prefix')->nullable()->after('payroll_locked_through');
        });
    }

    public function down(): void
    {
        Schema::table('hr_settings', function (Blueprint $table) {
            $table->dropColumn('employee_code_prefix');
        });
    }
};
