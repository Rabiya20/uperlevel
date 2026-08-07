<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_settings', function (Blueprint $table) {
            $table->string('payroll_cycle')->default('monthly'); // weekly|biweekly|monthly
            $table->unsignedTinyInteger('payroll_pay_day')->default(1);
            // Payroll is finalized through this date — leave requests
            // touching it are blocked until an admin clears the lock.
            $table->date('payroll_locked_through')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hr_settings', function (Blueprint $table) {
            $table->dropColumn(['payroll_cycle', 'payroll_pay_day', 'payroll_locked_through']);
        });
    }
};
