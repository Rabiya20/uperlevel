<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-employee override of their assigned shift's standard
            // start/end — e.g. Evening Shift is normally 6PM-2AM but this
            // one employee works 6PM-1AM. Null on both means "use the
            // shift's own hours"; only meaningful when shift_id is set.
            $table->time('custom_start_time')->nullable()->after('shift_id');
            $table->time('custom_end_time')->nullable()->after('custom_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['custom_start_time', 'custom_end_time']);
        });
    }
};
