<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->dateTime('captured_at');
            $table->string('device_name')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_captures');
    }
};
