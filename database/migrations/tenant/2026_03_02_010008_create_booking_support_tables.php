<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates booking support tables:
 *   - recurring_rules           — defines repetition pattern for recurring appointments
 *   - appointment_status_history — full audit trail of status changes
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Recurring rules ────────────────────────────────────────────
        Schema::create('recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('frequency', 20)
                  ->comment('daily|weekly|biweekly|monthly|custom');
            $table->tinyInteger('interval')->default(1)
                  ->comment('Every N periods (e.g. every 2 weeks)');
            $table->json('days_of_week')->nullable()
                  ->comment('[0,1,2] — used for weekly/biweekly');
            $table->date('ends_on')->nullable()
                  ->comment('Recurrence end date (null = forever)');
            $table->smallInteger('max_occurrences')->nullable()
                  ->comment('Cap on total generated appointments');
            $table->unsignedSmallInteger('generated_count')->default(0);
            $table->timestamps();

            $table->index('ends_on');
        });

        // ── 2. Appointment status history ─────────────────────────────────
        Schema::create('appointment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                  ->constrained('appointments')
                  ->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedBigInteger('changed_by')->nullable()
                  ->comment('user_id of actor (null = system)');
            $table->string('actor_type', 20)->nullable()
                  ->comment('user|staff|customer|system');
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable()
                  ->comment('IP address, user-agent, extra context');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['appointment_id', 'created_at'], 'idx_status_hist_appt');
            $table->index('changed_by');
        });

        // Now add the recurring_id FK to appointments that was deferred
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('recurring_id')
                  ->references('id')
                  ->on('recurring_rules')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['recurring_id']);
        });

        Schema::dropIfExists('appointment_status_history');
        Schema::dropIfExists('recurring_rules');
    }
};
