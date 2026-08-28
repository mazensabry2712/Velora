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
            $table->timestamp('email_verified_at')->nullable()->index();
            $table->string('email_verification_token_hash', 64)->nullable()->index();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->timestamp('email_verification_token_used_at')->nullable();
            $table->text('email_verification_token_encrypted')->nullable();
            $table->text('email_verification_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['email_verified_at']);
            $table->dropIndex(['email_verification_token_hash']);
            $table->dropColumn([
                'email_verified_at',
                'email_verification_token_hash',
                'email_verification_expires_at',
                'email_verification_token_used_at',
                'email_verification_token_encrypted',
                'email_verification_url',
            ]);
        });
    }
};
