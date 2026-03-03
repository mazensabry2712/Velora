<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades the existing `appointments` table to production-grade booking schema.
 *
 * Existing columns kept as-is (backward compatible):
 *   id, customer_id (→users), staff_id (→users, nullable), date, time_slot,
 *   status, notes, service_type, service_id, timestamps
 *
 * New columns added:
 *   - ULID for public-facing references
 *   - customer_id_new → customers (new FK once migrated)
 *   - staff_id_new → staff (new FK)
 *   - resource_id, group_id, recurring_id
 *   - starts_at / ends_at / ends_at_with_buffer (replaces date+time_slot)
 *   - timezone, pricing, discount, source
 *   - lifecycle timestamps (confirmed_at, completed_at, cancelled_at, no_show_at)
 *   - internal_notes, cancel_reason, metadata
 *   - soft deletes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Public reference ID (ULID, 26-char)
            $table->char('ulid', 26)->nullable()->unique()->after('id');

            // New relationships (will replace legacy columns after data migration)
            $table->foreignId('customer_id_new')
                  ->nullable()
                  ->after('ulid')
                  ->constrained('customers')
                  ->nullOnDelete();

            $table->foreignId('staff_id_new')
                  ->nullable()
                  ->after('customer_id_new')
                  ->constrained('staff')
                  ->nullOnDelete();

            // Resource & grouping
            $table->foreignId('resource_id')
                  ->nullable()
                  ->after('staff_id_new')
                  ->constrained('resources')
                  ->nullOnDelete();

            $table->unsignedBigInteger('group_id')->nullable()->after('resource_id')
                  ->comment('Groups concurrent group-booking appointments');

            $table->unsignedBigInteger('recurring_id')->nullable()->after('group_id')
                  ->comment('FK to recurring_rules once that table is created');

            // Precise datetime range (replaces date+time_slot)
            $table->datetime('starts_at')->nullable()->after('recurring_id');
            $table->datetime('ends_at')->nullable()->after('starts_at');
            $table->datetime('ends_at_with_buffer')->nullable()->after('ends_at')
                  ->comment('ends_at + buffer_after_minutes');

            $table->string('timezone', 50)->default('UTC')->after('ends_at_with_buffer');

            // Pricing (snapshot at booking time)
            $table->decimal('price', 10, 2)->default(0)->after('timezone');
            $table->decimal('deposit_paid', 10, 2)->default(0)->after('price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('deposit_paid');
            $table->string('discount_reason', 100)->nullable()->after('discount_amount');

            // Attendee count (for group bookings)
            $table->tinyInteger('attendees')->default(1)->after('discount_reason');

            // Booking source
            $table->string('source', 20)->default('online')->after('attendees')
                  ->comment('online|walk_in|phone|staff|import|api');

            // Internal-only notes (separate from customer-visible notes)
            $table->text('internal_notes')->nullable()->after('notes');

            // Cancellation details
            $table->string('cancelled_by', 20)->nullable()->after('internal_notes')
                  ->comment('customer|staff|system');
            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');

            // Lifecycle timestamps
            $table->datetime('confirmed_at')->nullable()->after('cancel_reason');
            $table->datetime('completed_at')->nullable()->after('confirmed_at');
            $table->datetime('cancelled_at')->nullable()->after('completed_at');
            $table->datetime('no_show_at')->nullable()->after('cancelled_at');
            $table->datetime('reminder_sent_at')->nullable()->after('no_show_at');

            // Extensible metadata
            $table->json('metadata')->nullable()->after('reminder_sent_at');

            // Soft deletes
            $table->softDeletes();

            // Performance indexes
            $table->index(['staff_id_new', 'starts_at'], 'idx_appt_staff_starts');
            $table->index(['customer_id_new', 'starts_at'], 'idx_appt_customer_starts');
            $table->index(['status', 'starts_at'], 'idx_appt_status_starts');
            $table->index(['starts_at', 'ends_at'], 'idx_appt_range');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['customer_id_new']);
            $table->dropForeign(['staff_id_new']);
            $table->dropForeign(['resource_id']);

            $table->dropIndex('idx_appt_staff_starts');
            $table->dropIndex('idx_appt_customer_starts');
            $table->dropIndex('idx_appt_status_starts');
            $table->dropIndex('idx_appt_range');
            $table->dropIndex(['source']);

            $table->dropColumn([
                'ulid', 'customer_id_new', 'staff_id_new', 'resource_id',
                'group_id', 'recurring_id', 'starts_at', 'ends_at',
                'ends_at_with_buffer', 'timezone', 'price', 'deposit_paid',
                'discount_amount', 'discount_reason', 'attendees', 'source',
                'internal_notes', 'cancelled_by', 'cancel_reason',
                'confirmed_at', 'completed_at', 'cancelled_at', 'no_show_at',
                'reminder_sent_at', 'metadata', 'deleted_at',
            ]);
        });
    }
};
