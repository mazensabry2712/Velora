<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates pre-aggregated analytics tables for fast dashboard queries.
 * Data is populated nightly by scheduled jobs — never queried from raw appointments.
 *
 * Tables:
 *   - analytics_daily       — business-level daily KPIs
 *   - staff_analytics_daily — per-staff daily metrics
 *   - booking_heatmap       — demand mapping (day_of_week × hour)
 *   - service_analytics_daily — per-service daily performance
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Business daily KPIs ────────────────────────────────────────
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();

            // Booking counts
            $table->unsignedSmallInteger('total_bookings')->default(0);
            $table->unsignedSmallInteger('confirmed')->default(0);
            $table->unsignedSmallInteger('completed')->default(0);
            $table->unsignedSmallInteger('cancelled')->default(0);
            $table->unsignedSmallInteger('no_shows')->default(0);
            $table->unsignedSmallInteger('pending')->default(0);
            $table->unsignedSmallInteger('rescheduled')->default(0);

            // Customer counts
            $table->unsignedSmallInteger('new_customers')->default(0);
            $table->unsignedSmallInteger('returning_customers')->default(0);
            $table->unsignedSmallInteger('unique_customers')->default(0);

            // Revenue
            $table->decimal('gross_revenue', 12, 2)->default(0);
            $table->decimal('net_revenue', 12, 2)->default(0)
                  ->comment('After discounts');
            $table->decimal('deposit_revenue', 10, 2)->default(0);
            $table->decimal('avg_booking_value', 10, 2)->default(0);

            // Utilization
            $table->decimal('utilization_pct', 5, 2)->default(0)
                  ->comment('Percentage of available slots booked');
            $table->unsignedSmallInteger('total_slots_available')->default(0);
            $table->unsignedSmallInteger('total_slots_booked')->default(0);

            // Online vs walk-in
            $table->unsignedSmallInteger('online_bookings')->default(0);
            $table->unsignedSmallInteger('walkin_bookings')->default(0);

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('date');
        });

        // ── 2. Per-staff daily metrics ────────────────────────────────────
        Schema::create('staff_analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');

            $table->unsignedSmallInteger('bookings_count')->default(0);
            $table->unsignedSmallInteger('completed')->default(0);
            $table->unsignedSmallInteger('cancelled')->default(0);
            $table->unsignedSmallInteger('no_shows')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('commission_earned', 10, 2)->default(0);
            $table->decimal('utilization_pct', 5, 2)->default(0);
            $table->unsignedSmallInteger('unique_customers')->default(0);

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'date'], 'uq_staff_analytics_date');
            $table->index('date');
        });

        // ── 3. Booking demand heatmap ─────────────────────────────────────
        Schema::create('booking_heatmap', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')
                  ->comment('ISO week start (Monday)');
            $table->tinyInteger('day_of_week')
                  ->comment('0=Sunday … 6=Saturday');
            $table->tinyInteger('hour_of_day')
                  ->comment('0–23');
            $table->unsignedSmallInteger('bookings_count')->default(0);
            $table->unsignedSmallInteger('revenue_cents')->default(0);
            $table->timestamps();

            $table->unique(['week_start', 'day_of_week', 'hour_of_day'], 'uq_heatmap');
            $table->index(['day_of_week', 'hour_of_day'], 'idx_heatmap_slot');
        });

        // ── 4. Per-service daily metrics ──────────────────────────────────
        Schema::create('service_analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->date('date');

            $table->unsignedSmallInteger('bookings_count')->default(0);
            $table->unsignedSmallInteger('completed')->default(0);
            $table->unsignedSmallInteger('cancelled')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('avg_booking_value', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['service_id', 'date'], 'uq_service_analytics_date');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_analytics_daily');
        Schema::dropIfExists('booking_heatmap');
        Schema::dropIfExists('staff_analytics_daily');
        Schema::dropIfExists('analytics_daily');
    }
};
