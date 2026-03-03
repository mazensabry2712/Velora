<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration was accidentally placed in central migrations.
        // Skip gracefully if the appointments table does not exist (central DB).
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('notes')->comment('1-5 stars rating');
            }
            if (! Schema::hasColumn('appointments', 'rating_comment')) {
                $table->text('rating_comment')->nullable()->after('rating');
            }
            if (! Schema::hasColumn('appointments', 'rated_at')) {
                $table->timestamp('rated_at')->nullable()->after('rating_comment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(
                array_filter(['rating', 'rating_comment', 'rated_at'], fn ($col) => Schema::hasColumn('appointments', $col))
            );
        });
    }
};
