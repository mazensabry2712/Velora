<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the dedicated `staff` table — the production entity for booking staff.
 * Decouples staff from the `users` table (users table still used for system login).
 * A staff member may optionally be linked to a user account (user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            // Optional link to a system user account (for staff portal login)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Identity
            $table->string('first_name', 80);
            $table->string('last_name', 80)->default('');
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_country', 4)->nullable()->comment('ISO-2 country code');
            $table->string('avatar', 255)->nullable();

            // Display
            $table->json('title')->nullable()->comment('{"en":"Senior Stylist","ar":"مصففة أولى"}');
            $table->json('bio')->nullable();
            $table->string('color', 7)->nullable()->comment('Calendar slot color hex');
            $table->smallInteger('sort_order')->default(0);

            // Scheduling settings
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('accepts_bookings')->default(true);
            $table->boolean('is_active')->default(true);

            // Commission
            $table->string('commission_type', 10)->default('none')
                  ->comment('none|fixed|percent');
            $table->decimal('commission_value', 7, 2)->default(0);

            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'accepts_bookings'], 'idx_staff_active');
            $table->unique('user_id');
        });

        // Migrate staff_services pivot to reference `staff.id` instead of `users.id`
        // Add staff_id column alongside existing user_id (new FK)
        Schema::table('staff_services', function (Blueprint $table) {
            $table->foreignId('staff_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('staff')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_services', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });

        Schema::dropIfExists('staff');
    }
};
