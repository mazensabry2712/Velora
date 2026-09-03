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
        if (! Schema::hasTable('appointments')) {
            return;
        }

        // Reconcile legacy user-owned appointment identities into the
        // canonical business entities before the legacy columns are removed.
        if (Schema::hasTable('customers') && Schema::hasTable('users') && Schema::hasColumn('appointments', 'customer_id')) {
            DB::table('appointments')
                ->whereNull('customer_id_new')
                ->whereNotNull('customer_id')
                ->orderBy('id')
                ->get(['id', 'customer_id'])
                ->each(function (object $appointment): void {
                    $customerId = DB::table('customers')
                        ->where('user_id', $appointment->customer_id)
                        ->value('id');

                    if ($customerId === null) {
                        $email = DB::table('users')
                            ->where('id', $appointment->customer_id)
                            ->value('email');

                        if ($email !== null) {
                            $matches = DB::table('customers')
                                ->where('email', $email)
                                ->pluck('id');

                            if ($matches->count() === 1) {
                                $customerId = $matches->first();
                            }
                        }
                    }

                    if ($customerId !== null) {
                        DB::table('appointments')
                            ->where('id', $appointment->id)
                            ->update(['customer_id_new' => $customerId]);
                    }
                });
        }

        if (Schema::hasTable('staff') && Schema::hasTable('users') && Schema::hasColumn('appointments', 'staff_id')) {
            DB::table('appointments')
                ->whereNull('staff_id_new')
                ->whereNotNull('staff_id')
                ->orderBy('id')
                ->get(['id', 'staff_id'])
                ->each(function (object $appointment): void {
                    $staffId = DB::table('staff')
                        ->where('user_id', $appointment->staff_id)
                        ->value('id');

                    if ($staffId !== null) {
                        DB::table('appointments')
                            ->where('id', $appointment->id)
                            ->update(['staff_id_new' => $staffId]);
                    }
                });
        }

        // Materialize the canonical time fields from the historical date/time
        // representation when they are still missing.
        if (Schema::hasColumn('appointments', 'starts_at')) {
            DB::statement(
                "UPDATE appointments
                 SET starts_at = TIMESTAMP(date, time_slot)
                 WHERE starts_at IS NULL
                   AND date IS NOT NULL
                   AND time_slot IS NOT NULL"
            );
        }

        if (Schema::hasColumn('appointments', 'ends_at') && Schema::hasColumn('appointments', 'starts_at')) {
            $hasServiceDuration = Schema::hasTable('services') && Schema::hasColumn('services', 'duration_minutes');

            if ($hasServiceDuration) {
                DB::statement(
                    "UPDATE appointments a
                     INNER JOIN services s ON s.id = a.service_id
                     SET a.ends_at = DATE_ADD(a.starts_at, INTERVAL COALESCE(s.duration_minutes, s.duration, 0) MINUTE)
                     WHERE a.ends_at IS NULL
                       AND a.starts_at IS NOT NULL"
                );
            }
        }

        if (Schema::hasColumn('appointments', 'ends_at_with_buffer') && Schema::hasColumn('appointments', 'ends_at')) {
            if (Schema::hasTable('services') && Schema::hasColumn('services', 'buffer_after_minutes')) {
                DB::statement(
                    "UPDATE appointments a
                     INNER JOIN services s ON s.id = a.service_id
                     SET a.ends_at_with_buffer = DATE_ADD(a.ends_at, INTERVAL COALESCE(s.buffer_after_minutes, 0) MINUTE)
                     WHERE a.ends_at_with_buffer IS NULL
                       AND a.ends_at IS NOT NULL"
                );
            } else {
                DB::statement(
                    "UPDATE appointments
                     SET ends_at_with_buffer = ends_at
                     WHERE ends_at_with_buffer IS NULL
                       AND ends_at IS NOT NULL"
                );
            }
        }

        $unresolvedCustomer = Schema::hasColumn('appointments', 'customer_id_new') && Schema::hasColumn('appointments', 'customer_id')
            ? DB::table('appointments')->whereNotNull('customer_id')->whereNull('customer_id_new')->count()
            : 0;
        $unresolvedStaff = Schema::hasColumn('appointments', 'staff_id_new') && Schema::hasColumn('appointments', 'staff_id')
            ? DB::table('appointments')->whereNotNull('staff_id')->whereNull('staff_id_new')->count()
            : 0;

        if ($unresolvedCustomer > 0 || $unresolvedStaff > 0) {
            throw new \RuntimeException(sprintf(
                'Appointment identity reconciliation failed: %d customer mappings and %d staff mappings remain unresolved.',
                $unresolvedCustomer,
                $unresolvedStaff,
            ));
        }
    }

    public function down(): void
    {
        // Data reconciliation is intentionally not reversed. The canonical
        // values are now the source of truth and may have been created after
        // this migration ran.
    }
};
