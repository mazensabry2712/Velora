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
        if (! Schema::hasTable('staff_services') || ! Schema::hasColumn('staff_services', 'user_id')) {
            return;
        }

        $unresolved = DB::table('staff_services')
            ->whereNotNull('user_id')
            ->whereNull('staff_id')
            ->count();

        if ($unresolved > 0) {
            throw new \RuntimeException(
                'Cannot remove staff_services.user_id: unresolved legacy rows remain.'
            );
        }

        $hasUserForeignKey = collect(Schema::getForeignKeys('staff_services'))
            ->contains(static fn (array $foreign): bool => in_array('user_id', $foreign['columns'] ?? [], true));

        if ($hasUserForeignKey) {
            Schema::table('staff_services', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('staff_services', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff_services') || Schema::hasColumn('staff_services', 'user_id')) {
            return;
        }

        Schema::table('staff_services', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('staff_id')->constrained('users')->nullOnDelete();
        });

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
    }
};
