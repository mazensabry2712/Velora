<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_vip column if it doesn't exist
        if (!Schema::hasColumn('queues', 'is_vip')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->boolean('is_vip')->default(false)->after('status');
            });
        }

        // Add counter_number column if it doesn't exist
        if (!Schema::hasColumn('queues', 'counter_number')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->integer('counter_number')->nullable()->after('is_vip');
            });
        }

        // Update status enum to include 'Serving' if needed
        // Note: MySQL doesn't easily support modifying ENUM, so we'll handle this in the application layer
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'is_vip')) {
                $table->dropColumn('is_vip');
            }
            if (Schema::hasColumn('queues', 'counter_number')) {
                $table->dropColumn('counter_number');
            }
        });
    }
};
