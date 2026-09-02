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
        if (! Schema::hasTable('staff_services') || ! Schema::hasColumn('staff_services', 'staff_id')) {
            return;
        }

        if (Schema::hasColumn('staff_services', 'user_id') && Schema::hasColumn('staff', 'user_id')) {
            DB::table('staff_services')
                ->whereNull('staff_id')
                ->orderBy('id')
                ->get(['id', 'user_id'])
                ->each(function (object $pivot): void {
                    if ($pivot->user_id === null) {
                        return;
                    }

                    $staffId = DB::table('staff')
                        ->where('user_id', $pivot->user_id)
                        ->value('id');

                    if ($staffId !== null) {
                        DB::table('staff_services')
                            ->where('id', $pivot->id)
                            ->update(['staff_id' => $staffId]);
                    }
                });

            Schema::table('staff_services', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        // Remove duplicate rows before adding the canonical uniqueness rule.
        $duplicates = DB::table('staff_services')
            ->select('staff_id', 'service_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as row_count'))
            ->whereNotNull('staff_id')
            ->groupBy('staff_id', 'service_id')
            ->having('row_count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('staff_services')
                ->where('staff_id', $duplicate->staff_id)
                ->where('service_id', $duplicate->service_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        $indexes = collect(Schema::getIndexes('staff_services'))->pluck('name');

        if ($indexes->contains('staff_services_user_id_service_id_unique')) {
            Schema::table('staff_services', function (Blueprint $table): void {
                $table->dropUnique(['user_id', 'service_id']);
            });
        }

        if (! $indexes->contains('staff_services_staff_id_service_id_unique')) {
            Schema::table('staff_services', function (Blueprint $table): void {
                $table->unique(['staff_id', 'service_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff_services')) {
            return;
        }

        if (Schema::hasColumn('staff_services', 'staff_id') && Schema::hasColumn('staff_services', 'user_id')) {
            DB::table('staff_services')
                ->whereNull('user_id')
                ->orderBy('id')
                ->get(['id', 'staff_id'])
                ->each(function (object $pivot): void {
                    if ($pivot->staff_id === null) {
                        return;
                    }

                    $userId = DB::table('staff')
                        ->where('id', $pivot->staff_id)
                        ->value('user_id');

                    if ($userId !== null) {
                        DB::table('staff_services')
                            ->where('id', $pivot->id)
                            ->update(['user_id' => $userId]);
                    }
                });

            $indexes = collect(Schema::getIndexes('staff_services'))->pluck('name');

            if ($indexes->contains('staff_services_staff_id_service_id_unique')) {
                Schema::table('staff_services', function (Blueprint $table): void {
                    $table->dropUnique(['staff_id', 'service_id']);
                });
            }

            Schema::table('staff_services', function (Blueprint $table): void {
                $table->unique(['user_id', 'service_id']);
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }
    }
};
