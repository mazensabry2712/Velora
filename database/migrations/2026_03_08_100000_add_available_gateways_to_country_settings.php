<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add available_gateways (JSON array) to country_settings.
 *
 * This replaces the old single-string payment_gateway column with a
 * JSON array, enabling per-country multi-gateway routing.
 *
 * Examples:
 *   EG → ["paymob", "stripe"]
 *   SA → ["moyasar", "stripe"]
 *   US → ["stripe", "paypal"]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_settings', function (Blueprint $table) {
            $table->json('available_gateways')
                  ->nullable()
                  ->after('payment_gateway')
                  ->comment('Ordered list of payment gateways for this country, e.g. ["moyasar","stripe"]');
        });

        // Seed available_gateways from the existing payment_gateway column
        // so existing rows get a sensible default immediately.
        $rows = DB::table('country_settings')->get();

        foreach ($rows as $row) {
            $primary   = $row->payment_gateway ?? 'stripe';
            $gateways  = [$primary];

            // Always include stripe as a fallback unless it is already primary
            if ($primary !== 'stripe') {
                $gateways[] = 'stripe';
            }

            DB::table('country_settings')
                ->where('id', $row->id)
                ->update(['available_gateways' => json_encode($gateways)]);
        }

        // Known multi-gateway overrides for common markets
        $overrides = [
            'EG' => ['paymob', 'stripe'],
            'SA' => ['moyasar', 'stripe'],
            'US' => ['stripe', 'paypal'],
            'GB' => ['stripe', 'paypal'],
            'EU' => ['stripe', 'paypal'],
            'AE' => ['stripe', 'moyasar'],
            'KW' => ['moyasar', 'stripe'],
            'QA' => ['moyasar', 'stripe'],
            'BH' => ['moyasar', 'stripe'],
        ];

        foreach ($overrides as $countryCode => $gateways) {
            DB::table('country_settings')
                ->where('country_code', $countryCode)
                ->update(['available_gateways' => json_encode($gateways)]);
        }
    }

    public function down(): void
    {
        Schema::table('country_settings', function (Blueprint $table) {
            $table->dropColumn('available_gateways');
        });
    }
};
