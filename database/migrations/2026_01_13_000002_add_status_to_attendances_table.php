<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Absent is the one attendance state that isn't self-evident from
    // check_in/check_out being null (that could just mean "not evaluated
    // yet" for a future date). "status" makes an explicit absence — either
    // admin-marked or auto-detected after the shift's off time — a real,
    // queryable record instead of something only ever inferred live.
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status')->nullable()->after('check_out'); // null|absent
            $table->foreignId('marked_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable()->after('marked_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_by');
            $table->dropColumn(['status', 'marked_at']);
        });
    }
};
