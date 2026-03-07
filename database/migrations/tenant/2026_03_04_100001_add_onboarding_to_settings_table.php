<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false)->after('available_languages');
            }
            if (! Schema::hasColumn('settings', 'onboarding_step')) {
                $table->unsignedTinyInteger('onboarding_step')->default(0)->after('onboarding_completed');
            }
            if (! Schema::hasColumn('settings', 'booking_enabled')) {
                $table->boolean('booking_enabled')->default(true)->after('onboarding_step');
            }
            if (! Schema::hasColumn('settings', 'queue_enabled')) {
                $table->boolean('queue_enabled')->default(true)->after('booking_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['onboarding_completed', 'onboarding_step', 'booking_enabled', 'queue_enabled'],
                fn ($c) => Schema::hasColumn('settings', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
