<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 10)->unique()->comment('ISO-3166-1 alpha-2 or GLOBAL');
            $table->string('country_name', 100);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('payment_methods')->comment('e.g. ["stripe","paypal","mada"]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_pricing');
    }
};
