<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the dedicated `customers` table.
 * Customers are booking-facing entities — distinct from admin `users`.
 * Includes GDPR compliance fields, LTV tier, and full-text search capability.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('first_name', 80);
            $table->string('last_name', 80)->default('');
            $table->string('email', 160)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('phone_country', 4)->nullable()->comment('ISO-2 country code');
            $table->date('dob')->nullable()->comment('Date of birth');
            $table->string('gender', 10)->nullable()->comment('male|female|other|prefer_not_to_say');
            $table->string('avatar', 255)->nullable();

            // Preferences
            $table->string('language', 10)->default('en')->comment('Preferred locale');
            $table->string('timezone', 50)->default('UTC');
            $table->text('notes')->nullable()->comment('Internal staff notes');
            $table->json('tags')->nullable()->comment('["vip","regular","sensitive"]');

            // Status flags
            $table->boolean('is_blocked')->default(false)
                  ->comment('Block this customer from booking');
            $table->string('block_reason', 255)->nullable();

            // GDPR
            $table->boolean('gdpr_consent')->default(false);
            $table->timestamp('gdpr_consent_at')->nullable();
            $table->string('gdpr_consent_ip', 45)->nullable();

            // Lifecycle / LTV
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->unsignedSmallInteger('total_visits')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->string('ltv_tier', 20)->default('new')
                  ->comment('new|regular|vip|at_risk|lost');

            // Source / acquisition
            $table->string('acquisition_source', 40)->nullable()
                  ->comment('walk_in|online|referral|social|import');
            $table->string('referral_code', 30)->nullable();

            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['ltv_tier', 'is_blocked'], 'idx_customers_ltv');
            $table->index('last_visit_at');
            $table->index('created_at');
        });

        // Full-text search index for customer lookup
        // Note: MySQL-only — wrapped in try/catch so SQLite tests don't break
        try {
            \DB::statement(
                'ALTER TABLE customers ADD FULLTEXT idx_customers_fulltext (first_name, last_name, email, phone)'
            );
        } catch (\Throwable) {
            // Silently skip on non-MySQL drivers (tests, SQLite)
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
