<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align the persisted subscription schema with the canonical lifecycle:
     * trial -> read_only -> locked -> deletion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tenant_subscriptions')) {
            return;
        }

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_subscriptions', 'read_only_ends_at')) {
                $table->timestamp('read_only_ends_at')->nullable()->after('trial_ends_at');
            }

            if (! Schema::hasColumn('tenant_subscriptions', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('read_only_ends_at');
            }

            if (! Schema::hasColumn('tenant_subscriptions', 'deletion_at')) {
                $table->timestamp('deletion_at')->nullable()->after('locked_at');
            }

            if (! Schema::hasColumn('tenant_subscriptions', 'grace_ends_at')) {
                $table->timestamp('grace_ends_at')->nullable()->after('deletion_at');
            }
        });

        // The original schema stored a legacy enum. MySQL must accept the
        // canonical lifecycle states used by the application now.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE tenant_subscriptions MODIFY status VARCHAR(32) NOT NULL DEFAULT 'trial'"
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_subscriptions')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE tenant_subscriptions MODIFY status ENUM('trial','active','grace','suspended','cancelled','expired') NOT NULL DEFAULT 'trial'"
            );
        }
    }
};
