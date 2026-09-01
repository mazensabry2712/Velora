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
        if (! Schema::hasTable('business_rules')) {
            return;
        }

        // The legacy schema may still contain `name`. The current model uses
        // `key` as the canonical identifier. Preserve the legacy column for
        // backward compatibility, but make it nullable so current upserts do
        // not require obsolete data.
        Schema::table('business_rules', function (Blueprint $table): void {
            if (Schema::hasColumn('business_rules', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });

        // Backfill the canonical key from legacy names when possible.
        if (Schema::hasColumn('business_rules', 'name') && Schema::hasColumn('business_rules', 'key')) {
            DB::table('business_rules')
                ->whereNull('key')
                ->whereNotNull('name')
                ->update(['key' => DB::raw('name')]);
        }
    }

    public function down(): void
    {
        // Compatibility migration; do not restore a NOT NULL legacy column
        // because existing canonical rows may not have a legacy name.
    }
};
