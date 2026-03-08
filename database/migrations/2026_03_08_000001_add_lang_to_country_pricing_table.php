<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_pricing', function (Blueprint $table) {
            if (! Schema::hasColumn('country_pricing', 'lang')) {
                $table->string('lang', 10)->nullable()->default(null)
                    ->after('currency')
                    ->comment('Default language code for this country, e.g. ar, en, fr');
            }
        });

        // Seed default lang values for known countries
        $defaults = [
            'SA' => 'ar', 'EG' => 'ar', 'AE' => 'ar',
            'KW' => 'ar', 'QA' => 'ar', 'BH' => 'ar',
            'OM' => 'ar', 'IQ' => 'ar', 'JO' => 'ar',
            'LB' => 'ar', 'LY' => 'ar', 'MA' => 'ar',
            'DZ' => 'ar', 'TN' => 'ar', 'YE' => 'ar',
            'SY' => 'ar', 'SD' => 'ar',
            'DE' => 'de', 'FR' => 'fr', 'ES' => 'es',
            'PT' => 'pt', 'BR' => 'pt', 'IT' => 'it',
            'RU' => 'ru', 'CN' => 'zh', 'JP' => 'ja',
        ];

        foreach ($defaults as $code => $lang) {
            \Illuminate\Support\Facades\DB::table('country_pricing')
                ->where('country_code', $code)
                ->whereNull('lang')
                ->update(['lang' => $lang]);
        }
    }

    public function down(): void
    {
        Schema::table('country_pricing', function (Blueprint $table) {
            if (Schema::hasColumn('country_pricing', 'lang')) {
                $table->dropColumn('lang');
            }
        });
    }
};
