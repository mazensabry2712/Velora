<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('notes')->comment('1-5 stars');
            }
            if (! Schema::hasColumn('appointments', 'rating_comment')) {
                $table->text('rating_comment')->nullable()->after('rating');
            }
            if (! Schema::hasColumn('appointments', 'rated_at')) {
                $table->timestamp('rated_at')->nullable()->after('rating_comment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            foreach (['rating', 'rating_comment', 'rated_at'] as $col) {
                if (Schema::hasColumn('appointments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
