<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('provisioning_status', 32)->nullable()->index();
            $table->string('provisioning_token_hash', 64)->nullable()->index();
            $table->timestamp('provisioning_token_used_at')->nullable();
            $table->string('provisioning_email', 191)->nullable();
            $table->text('provisioning_redirect_url')->nullable();
            $table->timestamp('provisioning_ready_at')->nullable();
            $table->text('provisioning_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['provisioning_status']);
            $table->dropIndex(['provisioning_token_hash']);
            $table->dropColumn([
                'provisioning_status',
                'provisioning_token_hash',
                'provisioning_token_used_at',
                'provisioning_email',
                'provisioning_redirect_url',
                'provisioning_ready_at',
                'provisioning_message',
            ]);
        });
    }
};
