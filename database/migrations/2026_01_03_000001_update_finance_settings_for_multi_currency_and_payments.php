<?php

use App\Models\FinanceSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->json('enabled_currencies')->nullable()->after('currency_symbol');
            $table->json('bank_accounts')->nullable()->after('bank_details');

            $table->string('payment_gateway_provider')->nullable()->after('accepted_payment_methods');
            $table->string('payment_gateway_mode', 10)->default('sandbox')->after('payment_gateway_provider');
            $table->string('payment_gateway_public_key')->nullable()->after('payment_gateway_mode');
            $table->text('payment_gateway_secret_key')->nullable()->after('payment_gateway_public_key');
            $table->text('payment_gateway_webhook_secret')->nullable()->after('payment_gateway_secret_key');

            $table->boolean('allow_partial_payments')->default(false)->after('late_fee_percentage');
            $table->unsignedTinyInteger('payment_reminder_days')->nullable()->after('allow_partial_payments');
        });

        // Freeform bank_details text doesn't map cleanly onto the new
        // structured columns — preserve it as a single migrated bank
        // account row rather than silently dropping whatever was saved.
        FinanceSettings::query()->whereNotNull('bank_details')->where('bank_details', '!=', '')->each(function (FinanceSettings $settings) {
            $settings->update([
                'bank_accounts' => [[
                    'bank_name' => 'Migrated',
                    'account_title' => '',
                    'account_number' => $settings->bank_details,
                    'iban' => '',
                    'currency_code' => $settings->currency_code,
                    'is_primary' => true,
                ]],
            ]);
        });

        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn('bank_details');
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->text('bank_details')->nullable()->after('accepted_payment_methods');
        });

        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn([
                'enabled_currencies',
                'bank_accounts',
                'payment_gateway_provider',
                'payment_gateway_mode',
                'payment_gateway_public_key',
                'payment_gateway_secret_key',
                'payment_gateway_webhook_secret',
                'allow_partial_payments',
                'payment_reminder_days',
            ]);
        });
    }
};
