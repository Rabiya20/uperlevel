<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Plain SQL rather than Schema::change() — the app doesn't have
// doctrine/dbal installed, which ->change() requires.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE activity_logs MODIFY description TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE activity_logs MODIFY description VARCHAR(255) NOT NULL');
    }
};
