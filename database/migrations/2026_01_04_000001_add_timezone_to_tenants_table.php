<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // IANA identifier (e.g. "Asia/Karachi"). Every timestamp shown
            // to a tenant's users — attendance, check-in banner, dates —
            // is stored in UTC and converted to this for display, so
            // changing it never rewrites historical data.
            $table->string('timezone', 64)->default('UTC')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
