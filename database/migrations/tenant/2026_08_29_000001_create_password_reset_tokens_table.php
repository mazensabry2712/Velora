<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token', 64);
                $table->string('locale', 10)->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        $columns = Schema::getColumnListing('password_reset_tokens');

        Schema::table('password_reset_tokens', function (Blueprint $table) use ($columns): void {
            if (! in_array('token', $columns, true)) {
                $table->string('token', 64);
            }

            if (! in_array('locale', $columns, true)) {
                $table->string('locale', 10)->nullable();
            }

            if (! in_array('created_at', $columns, true)) {
                $table->timestamp('created_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
