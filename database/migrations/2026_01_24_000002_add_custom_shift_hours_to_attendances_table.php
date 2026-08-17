<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Snapshotted from the user at check-in/manual-entry time, same
            // reasoning as shift_id above — editing an employee's custom
            // hours later must never rewrite the lateness/overtime math on
            // days already recorded.
            $table->time('custom_start_time')->nullable()->after('shift_id');
            $table->time('custom_end_time')->nullable()->after('custom_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['custom_start_time', 'custom_end_time']);
        });
    }
};
