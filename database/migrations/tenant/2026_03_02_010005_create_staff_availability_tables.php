<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates all staff availability and schedule tables:
 *   - staff_working_hours  — weekly schedule per staff member
 *   - staff_breaks         — recurring break windows (e.g. lunch)
 *   - staff_time_off       — one-off leave / day-off ranges
 *   - holidays             — business-level public holidays
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Weekly working hours ──────────────────────────────────────────
        Schema::create('staff_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')
                  ->comment('0=Sunday … 6=Saturday');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_working')->default(true);
            $table->timestamps();

            $table->unique(['staff_id', 'day_of_week'], 'uq_staff_working_hours');
            $table->index(['staff_id', 'is_working']);
        });

        // ── 2. Recurring daily breaks ────────────────────────────────────────
        Schema::create('staff_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')
                  ->comment('0-6, or NULL = every working day');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label', 80)->nullable()->comment('e.g. "Lunch", "Prayer Break"');
            $table->timestamps();

            $table->index(['staff_id', 'day_of_week']);
        });

        // ── 3. One-off time-off / leave ──────────────────────────────────────
        Schema::create('staff_time_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('all_day')->default(true);
            $table->time('start_time')->nullable()
                  ->comment('Used when all_day = false');
            $table->time('end_time')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('status', 20)->default('approved')
                  ->comment('pending|approved|rejected');
            $table->timestamps();

            $table->index(['staff_id', 'start_date', 'end_date'], 'idx_time_off_range');
            $table->index(['start_date', 'end_date'], 'idx_time_off_dates');
        });

        // ── 4. Business-level public holidays ───────────────────────────────
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->json('name')->comment('{"en":"New Year","ar":"رأس السنة"}');
            $table->boolean('applies_to_all')->default(true)
                  ->comment('False = per-staff, use holiday_staff pivot');
            $table->timestamps();

            $table->index('date');
        });

        // Optional: per-staff holiday overrides (if applies_to_all = false)
        Schema::create('holiday_staff', function (Blueprint $table) {
            $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->primary(['holiday_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_staff');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('staff_time_off');
        Schema::dropIfExists('staff_breaks');
        Schema::dropIfExists('staff_working_hours');
    }
};
