<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->after('role');
            $table->string('phone')->nullable()->after('designation');
            $table->string('department')->nullable()->after('phone');
            $table->string('gender')->nullable()->after('department');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->date('date_of_joining')->nullable()->after('date_of_birth');
            $table->string('employment_type')->default('full_time')->after('date_of_joining'); // full_time|part_time|contract|intern
            $table->string('employment_status')->default('active')->after('employment_type'); // active|on_leave|terminated
            $table->string('cnic')->nullable()->after('employment_status');
            $table->text('address')->nullable()->after('cnic');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->foreignId('reporting_manager_id')->nullable()->after('emergency_contact_phone')
                ->constrained('users')->nullOnDelete();
            $table->decimal('basic_salary', 12, 2)->nullable()->after('reporting_manager_id');

            $table->unique(['tenant_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'employee_code']);
            $table->dropConstrainedForeignId('reporting_manager_id');
            $table->dropColumn([
                'employee_code', 'phone', 'department', 'gender', 'date_of_birth', 'date_of_joining',
                'employment_type', 'employment_status', 'cnic', 'address',
                'emergency_contact_name', 'emergency_contact_phone', 'basic_salary',
            ]);
        });
    }
};
