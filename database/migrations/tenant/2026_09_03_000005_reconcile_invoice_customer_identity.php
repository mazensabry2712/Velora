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
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('customers')) {
            return;
        }

        // The deployed invoice.customer_id historically points to users.
        // Stage canonical customer IDs before changing the FK.
        if (! Schema::hasColumn('invoices', 'customer_id_canonical')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->unsignedBigInteger('customer_id_canonical')->nullable()->after('customer_id');
            });
        }

        DB::table('invoices')
            ->whereNull('customer_id_canonical')
            ->orderBy('id')
            ->get(['id', 'customer_id'])
            ->each(function (object $invoice): void {
                if ($invoice->customer_id === null) {
                    return;
                }

                $customerId = DB::table('customers')
                    ->where('user_id', $invoice->customer_id)
                    ->value('id');

                if ($customerId !== null) {
                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update(['customer_id_canonical' => $customerId]);
                }
            });

        $unresolved = DB::table('invoices')
            ->whereNotNull('customer_id')
            ->whereNull('customer_id_canonical')
            ->count();

        if ($unresolved > 0) {
            throw new \RuntimeException(
                'Cannot reconcile invoices.customer_id: unresolved user-to-customer mappings remain.'
            );
        }

        $foreignKeys = collect(Schema::getForeignKeys('invoices'));
        $customerForeign = $foreignKeys->first(
            static fn (array $foreign): bool => in_array('customer_id', $foreign['columns'] ?? [], true)
        );

        if ($customerForeign !== null) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropForeign(['customer_id']);
            });
        }

        if (Schema::hasColumn('invoices', 'customer_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('customer_id');
            });
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('id')
                ->constrained('customers')
                ->nullOnDelete();
        });

        DB::table('invoices')
            ->whereNotNull('customer_id_canonical')
            ->orderBy('id')
            ->get(['id', 'customer_id_canonical'])
            ->each(function (object $invoice): void {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update(['customer_id' => $invoice->customer_id_canonical]);
            });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('customer_id_canonical');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('customers')) {
            return;
        }

        if (! Schema::hasColumn('invoices', 'customer_id')) {
            return;
        }

        // Restore the legacy users identity for rollback by resolving each
        // canonical customer to its optional user account.
        if (collect(Schema::getForeignKeys('invoices'))
            ->contains(static fn (array $foreign): bool => in_array('customer_id', $foreign['columns'] ?? [], true))) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropForeign(['customer_id']);
            });
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id_legacy')->nullable()->after('id');
        });

        DB::table('invoices')
            ->orderBy('id')
            ->get(['id', 'customer_id'])
            ->each(function (object $invoice): void {
                $userId = DB::table('customers')
                    ->where('id', $invoice->customer_id)
                    ->value('user_id');

                if ($userId !== null) {
                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update(['customer_id_legacy' => $userId]);
                }
            });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('customer_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('invoices')
            ->whereNotNull('customer_id_legacy')
            ->orderBy('id')
            ->get(['id', 'customer_id_legacy'])
            ->each(function (object $invoice): void {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update(['customer_id' => $invoice->customer_id_legacy]);
            });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('customer_id_legacy');
        });
    }
};
