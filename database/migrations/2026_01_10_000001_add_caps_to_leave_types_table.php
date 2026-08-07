<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Cap on days of this type takeable within a single calendar month. Null = no cap.
            $table->unsignedSmallInteger('max_per_month')->nullable()->after('days_per_year');
            // Cap on how many unused days roll over into next year. Null (with carry_forward
            // on) = the full unused balance carries, uncapped.
            $table->unsignedSmallInteger('max_carry_forward_days')->nullable()->after('carry_forward');
            // Ceiling on total banked balance (this year's allowance + carried-in) at any
            // time — the max an employee can be sitting on to use later or cash out at FNF.
            $table->unsignedSmallInteger('max_accumulation_days')->nullable()->after('max_carry_forward_days');
            // Whether unused days of this type are paid out in cash at Full & Final settlement.
            $table->boolean('is_encashable')->default(false)->after('max_accumulation_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['max_per_month', 'max_carry_forward_days', 'max_accumulation_days', 'is_encashable']);
        });
    }
};
