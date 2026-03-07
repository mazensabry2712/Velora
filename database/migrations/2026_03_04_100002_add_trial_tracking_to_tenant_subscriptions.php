<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add trial conversion tracking fields to tenant_subscriptions.
 * Enables MRR, Trial→Paid rate, Time-to-Aha KPI dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            // When trial was activated (onboarding completed)
            $table->timestamp('activated_at')->nullable()->after('trial_ends_at');
            // When trial converted to paid
            $table->timestamp('converted_at')->nullable()->after('activated_at');
            // JSON: which nudge days have been sent {1: true, 3: true, ...}
            $table->json('nudges_sent')->nullable()->after('converted_at');
            // Aha moment reached (≥5 completed appts + 1 successful reminder)
            $table->boolean('aha_reached')->default(false)->after('nudges_sent');
            $table->timestamp('aha_reached_at')->nullable()->after('aha_reached');
            // Founder alert sent flag
            $table->boolean('founder_alerted')->default(false)->after('aha_reached_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'activated_at', 'converted_at', 'nudges_sent',
                'aha_reached', 'aha_reached_at', 'founder_alerted',
            ]);
        });
    }
};
