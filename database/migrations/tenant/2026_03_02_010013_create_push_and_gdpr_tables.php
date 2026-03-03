<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates push notification and GDPR compliance tables:
 *   - push_tokens           — FCM/APNs/Web Push device tokens per owner
 *   - gdpr_consents         — granular consent records per customer
 *   - data_export_requests  — GDPR "Right to Data Portability" requests
 *   - deletion_requests     — GDPR "Right to Erasure" requests
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Push / device tokens ───────────────────────────────────────
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 20)
                  ->comment('customer|staff');
            $table->unsignedBigInteger('owner_id');
            $table->string('platform', 10)
                  ->comment('android|ios|web');
            $table->string('token', 512)->unique();
            $table->string('device_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'is_active'], 'idx_push_owner');
            $table->index(['platform', 'is_active']);
        });

        // ── 2. Granular GDPR consents ─────────────────────────────────────
        Schema::create('gdpr_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->string('type', 40)
                  ->comment('marketing_email|marketing_sms|data_processing|analytics|cookies|third_party_sharing');
            $table->boolean('granted')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source', 40)->nullable()
                  ->comment('booking_form|settings_page|import|api');
            $table->string('legal_basis', 40)->nullable()
                  ->comment('consent|legitimate_interest|contract|legal_obligation');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['customer_id', 'type'], 'uq_gdpr_consent_type');
            $table->index(['customer_id', 'granted']);
            $table->index('type');
        });

        // ── 3. Data export requests (GDPR Article 20) ────────────────────
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->string('status', 20)->default('pending')
                  ->comment('pending|processing|completed|failed|expired');
            $table->string('format', 10)->default('json')
                  ->comment('json|csv|pdf');
            $table->string('file_path', 255)->nullable();
            $table->string('download_token', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable()
                  ->comment('Download link expiry');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable()
                  ->comment('admin user_id');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['status', 'expires_at'], 'idx_export_expiry');
        });

        // ── 4. Deletion / erasure requests (GDPR Article 17) ────────────
        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->string('status', 20)->default('pending')
                  ->comment('pending|approved|rejected|completed|on_hold');
            $table->string('reason', 255)->nullable()
                  ->comment('Customer-provided reason');
            $table->text('rejection_reason')->nullable();
            $table->boolean('retain_anonymized')->default(false)
                  ->comment('Keep anonymized analytics data after erasure');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable()
                  ->comment('admin user_id');
            $table->text('notes')->nullable()
                  ->comment('Internal admin notes');
            $table->json('deleted_entities')->nullable()
                  ->comment('Audit log of what was deleted');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['status', 'requested_at'], 'idx_deletion_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
        Schema::dropIfExists('data_export_requests');
        Schema::dropIfExists('gdpr_consents');
        Schema::dropIfExists('push_tokens');
    }
};
