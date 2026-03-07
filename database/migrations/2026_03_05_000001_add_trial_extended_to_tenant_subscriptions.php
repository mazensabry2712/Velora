<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add trial extension tracking to tenant_subscriptions.
 * trial_extended: one-time 7-day extension flag (can only extend once)
 * trial_extended_at: when the extension was granted
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->boolean('trial_extended')->default(false)->after('founder_alerted');
            $table->timestamp('trial_extended_at')->nullable()->after('trial_extended');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_extended', 'trial_extended_at']);
        });
    }
};
