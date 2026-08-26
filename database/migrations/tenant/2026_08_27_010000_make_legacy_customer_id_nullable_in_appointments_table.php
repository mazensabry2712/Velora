<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legacy customer_id column is no longer populated by the public
     * booking flow. New bookings use customer_id_new instead.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    /**
     * Restore the legacy column to its original required state.
     *
     * This rollback is only safe when all appointment rows have a legacy
     * customer_id populated.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
