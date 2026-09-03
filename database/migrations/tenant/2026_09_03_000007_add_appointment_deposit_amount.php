<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'deposit_amount')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('deposit_paid');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'deposit_amount')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('deposit_amount');
        });
    }
};
