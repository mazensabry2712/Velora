<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('is_popular')
                    ->comment('Stripe Price ID for checkout sessions');
            }
            if (!Schema::hasColumn('subscription_plans', 'stripe_product_id')) {
                $table->string('stripe_product_id')->nullable()->after('stripe_price_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id', 'stripe_product_id']);
        });
    }
};
