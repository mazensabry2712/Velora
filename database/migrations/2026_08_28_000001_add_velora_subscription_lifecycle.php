<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->timestamp('read_only_ends_at')->nullable()->after('trial_ends_at');
            $table->timestamp('locked_at')->nullable()->after('read_only_ends_at');
            $table->timestamp('deletion_at')->nullable()->after('locked_at');
            $table->index('deletion_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenant_subscriptions MODIFY status ENUM('trial','active','grace','suspended','cancelled','expired','read_only','locked') NOT NULL DEFAULT 'trial'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenant_subscriptions MODIFY status ENUM('trial','active','grace','suspended','cancelled','expired') NOT NULL DEFAULT 'trial'");
        }

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['deletion_at']);
            $table->dropColumn(['read_only_ends_at', 'locked_at', 'deletion_at']);
        });
    }
};
