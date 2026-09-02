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
        if (! Schema::hasTable('staff_working_hours')) {
            return;
        }

        if (Schema::hasColumn('staff_working_hours', 'start_time')) {
            Schema::table('staff_working_hours', function (Blueprint $table): void {
                $table->time('start_time')->nullable()->change();
                $table->time('end_time')->nullable()->change();
            });
        }

        if (! Schema::hasTable('staff_schedules')) {
            return;
        }

        DB::table('staff_schedules')
            ->orderBy('id')
            ->get()
            ->each(function (object $schedule): void {
                $staffId = DB::table('staff')
                    ->where('user_id', $schedule->user_id)
                    ->value('id');

                if ($staffId === null) {
                    return;
                }

                $exists = DB::table('staff_working_hours')
                    ->where('staff_id', $staffId)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('staff_working_hours')->insert([
                    'staff_id' => $staffId,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'is_working' => (bool) $schedule->is_active,
                    'created_at' => $schedule->created_at ?? now(),
                    'updated_at' => $schedule->updated_at ?? now(),
                ]);
            });

        Schema::dropIfExists('staff_schedules');
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff_schedules')) {
            Schema::create('staff_schedules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'day_of_week']);
            });
        }

        if (Schema::hasTable('staff_working_hours') && Schema::hasTable('staff')) {
            DB::table('staff_working_hours')
                ->join('staff', 'staff.id', '=', 'staff_working_hours.staff_id')
                ->whereNotNull('staff.user_id')
                ->orderBy('staff_working_hours.id')
                ->get([
                    'staff.user_id',
                    'staff_working_hours.day_of_week',
                    'staff_working_hours.start_time',
                    'staff_working_hours.end_time',
                    'staff_working_hours.is_working',
                    'staff_working_hours.created_at',
                    'staff_working_hours.updated_at',
                ])
                ->each(function (object $schedule): void {
                    $exists = DB::table('staff_schedules')
                        ->where('user_id', $schedule->user_id)
                        ->where('day_of_week', $schedule->day_of_week)
                        ->exists();

                    if ($exists) {
                        return;
                    }

                    DB::table('staff_schedules')->insert([
                        'user_id' => $schedule->user_id,
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time ?? '00:00:00',
                        'end_time' => $schedule->end_time ?? '00:00:00',
                        'is_active' => (bool) $schedule->is_working,
                        'created_at' => $schedule->created_at ?? now(),
                        'updated_at' => $schedule->updated_at ?? now(),
                    ]);
                });
        }
    }
};
