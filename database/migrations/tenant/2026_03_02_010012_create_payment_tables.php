<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates payment and commission tables:
 *   - payment_transactions — gateway payment records (append-only ledger)
 *   - invoice_items        — line items linked to existing `invoices` table
 *   - staff_commissions    — per-transaction commission tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Payment transactions ───────────────────────────────────────
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();

            $table->foreignId('appointment_id')
                  ->nullable()
                  ->constrained('appointments')
                  ->nullOnDelete();

            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // Gateway info
            $table->string('gateway', 30)
                  ->comment('stripe|paypal|tap|hyperpay|moyasar|cash|manual');
            $table->string('gateway_transaction_id', 120)->nullable()->unique();
            $table->string('gateway_intent_id', 120)->nullable();

            // Transaction type and state
            $table->string('type', 20)
                  ->comment('charge|deposit|refund|partial_refund|manual');
            $table->string('status', 20)->default('pending')
                  ->comment('pending|processing|succeeded|failed|refunded|disputed');

            // Amounts (stored in smallest currency unit for precision)
            $table->unsignedBigInteger('amount')
                  ->comment('In smallest currency unit (cents/halalas)');
            $table->unsignedBigInteger('gateway_fee')->default(0);
            $table->unsignedBigInteger('net_amount')
                  ->comment('amount - gateway_fee');
            $table->char('currency', 3)->default('USD');

            // Refund tracking
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->foreignId('refund_of')
                  ->nullable()
                  ->constrained('payment_transactions')
                  ->nullOnDelete();
            $table->string('refund_reason', 255)->nullable();

            // Meta
            $table->json('gateway_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'type', 'status'], 'idx_txn_appt');
            $table->index(['customer_id', 'status'], 'idx_txn_customer');
            $table->index(['gateway', 'status', 'processed_at'], 'idx_txn_gateway');
        });

        // ── 2. Invoice line items ─────────────────────────────────────────
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();
            $table->json('description')
                  ->comment('{"en":"Haircut","ar":"قص شعر"}');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->unsignedBigInteger('unit_price')
                  ->comment('In smallest currency unit');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total')
                  ->comment('(quantity × unit_price) - discount');
            $table->char('currency', 3)->default('USD');
            $table->string('item_type', 20)->default('service')
                  ->comment('service|product|tax|tip|deposit|refund');
            $table->timestamps();

            $table->index('invoice_id');
        });

        // ── 3. Staff commissions ──────────────────────────────────────────
        Schema::create('staff_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                  ->constrained('staff')
                  ->cascadeOnDelete();
            $table->foreignId('appointment_id')
                  ->nullable()
                  ->constrained('appointments')
                  ->nullOnDelete();
            $table->foreignId('transaction_id')
                  ->nullable()
                  ->constrained('payment_transactions')
                  ->nullOnDelete();

            $table->unsignedBigInteger('gross_amount')
                  ->comment('Booking total (smallest unit)');
            $table->string('commission_type', 10)
                  ->comment('none|fixed|percent');
            $table->decimal('commission_rate', 7, 4)->default(0)
                  ->comment('Percent as decimal (0.15 = 15%) or fixed value');
            $table->unsignedBigInteger('commission_amount')
                  ->comment('Calculated commission (smallest unit)');
            $table->char('currency', 3)->default('USD');

            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_batch_id', 60)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'is_paid'], 'idx_commission_unpaid');
            $table->index(['staff_id', 'created_at'], 'idx_commission_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_commissions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('payment_transactions');
    }
};
