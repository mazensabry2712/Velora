<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `waiting_list` table.
 * Customers can join a waiting list when their preferred slot is unavailable.
 * When a cancellation opens a slot, the system auto-notifies waiting customers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            $table->foreignId('staff_id')
                  ->nullable()
                  ->constrained('staff')
                  ->nullOnDelete();

            // Date preferences
            $table->date('preferred_date')->nullable()
                  ->comment('Null = any available date');
            $table->json('preferred_days')->nullable()
                  ->comment('[0,1,5] day_of_week preferences');
            $table->time('preferred_time_from')->nullable();
            $table->time('preferred_time_to')->nullable();

            // Status lifecycle
            $table->string('status', 20)->default('waiting')
                  ->comment('waiting|notified|booked|expired|cancelled');

            // Notification tracking
            $table->timestamp('notified_at')->nullable();
            $table->tinyInteger('notification_count')->default(0);
            $table->timestamp('expires_at')->nullable()
                  ->comment('Auto-expire if no action taken within window');

            // Converted booking
            $table->foreignId('converted_appointment_id')
                  ->nullable()
                  ->constrained('appointments')
                  ->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(
                ['customer_id', 'service_id', 'staff_id', 'preferred_date'],
                'uq_waiting_list'
            );

            $table->index(['service_id', 'status', 'preferred_date'], 'idx_wl_service_status');
            $table->index(['status', 'expires_at'], 'idx_wl_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list');
    }
};
