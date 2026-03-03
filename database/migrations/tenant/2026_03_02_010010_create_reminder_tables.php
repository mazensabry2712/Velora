<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates automation tables:
 *   - reminder_rules    — configurable reminder triggers (when, channel, template)
 *   - reminder_logs     — per-appointment send audit trail
 *   - business_rules    — general purpose trigger→action rules engine
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Reminder rules ─────────────────────────────────────────────
        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('{"en":"24h Reminder","ar":"تذكير قبل 24 ساعة"}');
            $table->string('trigger_type', 30)
                  ->comment('before_appointment|after_appointment|after_booking|after_cancellation|after_no_show');
            $table->smallInteger('trigger_minutes')->default(1440)
                  ->comment('Minutes relative to trigger event (+before, -after means use positive for after)');
            $table->string('channel', 20)
                  ->comment('email|sms|whatsapp|push');
            $table->string('template_key', 100)->nullable()
                  ->comment('Mail/notification template identifier');
            $table->json('template_vars')->nullable()
                  ->comment('Mergeable variable defaults');
            $table->boolean('send_to_customer')->default(true);
            $table->boolean('send_to_staff')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'trigger_type'], 'idx_reminder_active');
        });

        // ── 2. Reminder send logs ─────────────────────────────────────────
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                  ->constrained('appointments')
                  ->cascadeOnDelete();
            $table->foreignId('rule_id')
                  ->nullable()
                  ->constrained('reminder_rules')
                  ->nullOnDelete();
            $table->string('channel', 20);
            $table->string('recipient', 160)->nullable()
                  ->comment('Email address or phone number');
            $table->string('status', 20)->default('pending')
                  ->comment('pending|sent|failed|skipped');
            $table->string('gateway_message_id', 100)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['appointment_id', 'channel'], 'idx_rlog_appt');
            $table->index(['status', 'scheduled_at'], 'idx_rlog_pending');
        });

        // ── 3. Business rules engine ──────────────────────────────────────
        Schema::create('business_rules', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('{"en":"Block short-notice bookings","ar":"..."}');
            $table->string('type', 40)
                  ->comment('booking_window|cancellation_window|deposit_required|capacity_limit|blackout|custom');
            $table->json('conditions')
                  ->comment('[{"field":"hours_before","operator":"<","value":2}]');
            $table->json('actions')
                  ->comment('[{"type":"block","message_key":"too_short_notice"}]');
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('priority')->default(10)
                  ->comment('Lower = evaluated first');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'type', 'priority'], 'idx_brule_eval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_rules');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('reminder_rules');
    }
};
