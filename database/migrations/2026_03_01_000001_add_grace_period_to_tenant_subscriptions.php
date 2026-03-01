<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            // Grace period end date (3 days after trial expires)
            if (!Schema::hasColumn('tenant_subscriptions', 'grace_ends_at')) {
                $table->timestamp('grace_ends_at')->nullable()->after('trial_ends_at');
            }
            // Stripe subscription ID for auto-renewal
            if (!Schema::hasColumn('tenant_subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('payment_method');
            }
            // Stripe customer ID
            if (!Schema::hasColumn('tenant_subscriptions', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('stripe_subscription_id');
            }
            // Stripe price ID
            if (!Schema::hasColumn('tenant_subscriptions', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('stripe_customer_id');
            }
            // Idempotency key for webhooks
            if (!Schema::hasColumn('tenant_subscriptions', 'last_webhook_event')) {
                $table->string('last_webhook_event')->nullable()->after('stripe_price_id');
            }
        });

        // Add stripe_customer_id to tenants data (no schema change needed - stored in JSON)
        // Add subdomain uniqueness index to domains if not exists
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'grace_ends_at',
                'stripe_subscription_id',
                'stripe_customer_id',
                'stripe_price_id',
                'last_webhook_event',
            ]);
        });
    }
};
