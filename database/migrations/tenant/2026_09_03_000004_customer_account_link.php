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
        if (! Schema::hasTable('customers') || ! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('customers', 'user_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->unique('user_id', 'uq_customers_user_id');
            });
        }

        // Link customers to an existing login account only when the email is
        // an unambiguous match. Guest customers remain account-less.
        DB::table('customers')
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function (object $customer): void {
                $userId = DB::table('users')
                    ->where('email', $customer->email)
                    ->value('id');

                if ($userId !== null) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'user_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('uq_customers_user_id');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
