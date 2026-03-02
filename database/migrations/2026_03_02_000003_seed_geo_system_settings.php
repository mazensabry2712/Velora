<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SystemSetting;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'default_language',            'value' => 'en',    'type' => 'string',  'group' => 'localization'],
            ['key' => 'default_currency',            'value' => 'USD',   'type' => 'string',  'group' => 'localization'],
            ['key' => 'geo_pricing_enabled',         'value' => '1',     'type' => 'boolean', 'group' => 'geo'],
            ['key' => 'geo_detection_enabled',       'value' => '1',     'type' => 'boolean', 'group' => 'geo'],
            ['key' => 'allow_manual_currency_switch','value' => '1',     'type' => 'boolean', 'group' => 'geo'],
            ['key' => 'allow_manual_language_switch','value' => '1',     'type' => 'boolean', 'group' => 'geo'],
            ['key' => 'enable_vat_per_country',      'value' => '1',     'type' => 'boolean', 'group' => 'geo'],
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }

    public function down(): void
    {
        $keys = [
            'default_language','default_currency','geo_pricing_enabled',
            'geo_detection_enabled','allow_manual_currency_switch',
            'allow_manual_language_switch','enable_vat_per_country',
        ];
        SystemSetting::whereIn('key', $keys)->delete();
    }
};
