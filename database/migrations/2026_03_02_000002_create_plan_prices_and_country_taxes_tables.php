<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('country_code', 2)->nullable(); // null = default/global
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 10, 2);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'country_code']);
            $table->index(['plan_id', 'is_default']);
        });

        Schema::create('country_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->string('tax_name', 50)->default('VAT'); // e.g. VAT, GST, TVA
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_taxes');
        Schema::dropIfExists('plan_prices');
    }
};
