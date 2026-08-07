<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('modules')->cascadeOnDelete();

            // which portal / layout this nav item belongs to
            $table->string('portal'); // superadmin | admin | employee

            $table->string('key');           // unique-ish machine name, e.g. "finance"
            $table->string('name');          // display label, e.g. "Accounts & Finance"
            $table->string('icon')->nullable();     // tabler-style icon key used by the blade icon partial
            $table->string('route_name')->nullable(); // named route, null = not built yet ("#")

            // comma separated list of roles allowed to see this item
            // e.g. "owner,admin,manager"
            $table->string('roles');

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
