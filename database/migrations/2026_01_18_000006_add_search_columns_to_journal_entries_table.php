<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalized onto the entry at posting time (from whichever source
 * produced it) so the per-account ledger can search/filter without a
 * polymorphic join per row — see LedgerPoster.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('memo');
            $table->string('payee')->nullable()->after('reference_number');
            $table->string('transaction_type')->nullable()->after('payee'); // invoice, payment, expense, manual
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'payee', 'transaction_type']);
        });
    }
};
