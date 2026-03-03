<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades the existing `services` table to production-grade booking schema.
 * Existing columns: id, name, name_ar, description, duration, price, is_active, timestamps
 * Adds: category_id, multilingual name, buffer times, deposit, capacity, slug, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Category relationship
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('service_categories')
                  ->nullOnDelete();

            // Multi-language name stored as JSON (alongside existing name/name_ar)
            $table->json('name_i18n')->nullable()->after('name_ar');
            $table->json('description_i18n')->nullable()->after('description');

            // URL slug
            $table->string('slug', 150)->nullable()->unique()->after('name_i18n');

            // Rename duration to duration_minutes context (add new, keep old for compat)
            $table->smallInteger('duration_minutes')->default(30)->after('slug')
                  ->comment('Appointment duration in minutes');
            $table->smallInteger('buffer_before_minutes')->default(0)->after('duration_minutes');
            $table->smallInteger('buffer_after_minutes')->default(0)->after('buffer_before_minutes');

            // Deposit
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('price');
            $table->tinyInteger('deposit_pct')->default(0)->after('deposit_amount')
                  ->comment('0=fixed amount, 1-100=percent of price');

            // Group / capacity
            $table->tinyInteger('max_capacity')->default(1)->after('deposit_pct')
                  ->comment('Max attendees, 1 = individual session');
            $table->boolean('is_group')->default(false)->after('max_capacity');

            // Booking settings
            $table->boolean('is_online_bookable')->default(true)->after('is_active');
            $table->string('image', 255)->nullable()->after('is_online_bookable');
            $table->smallInteger('sort_order')->default(0)->after('image');
            $table->json('metadata')->nullable()->after('sort_order');

            // Soft delete
            $table->softDeletes();

            // Indexes
            $table->index(['category_id', 'is_active'], 'idx_svc_category_active');
            $table->index(['is_online_bookable', 'is_active'], 'idx_svc_bookable');
        });

        // Upgrade staff_services pivot: add price & duration overrides
        Schema::table('staff_services', function (Blueprint $table) {
            $table->decimal('override_price', 10, 2)->nullable()->after('service_id');
            $table->smallInteger('override_duration')->nullable()->after('override_price')
                  ->comment('Override service duration in minutes for this staff member');
        });
    }

    public function down(): void
    {
        Schema::table('staff_services', function (Blueprint $table) {
            $table->dropColumn(['override_price', 'override_duration']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex('idx_svc_category_active');
            $table->dropIndex('idx_svc_bookable');
            $table->dropColumn([
                'category_id', 'name_i18n', 'description_i18n', 'slug',
                'duration_minutes', 'buffer_before_minutes', 'buffer_after_minutes',
                'deposit_amount', 'deposit_pct', 'max_capacity', 'is_group',
                'is_online_bookable', 'image', 'sort_order', 'metadata', 'deleted_at',
            ]);
        });
    }
};
