<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->string('public_reference', 32)->nullable()->index();
            $table->string('event', 80);
            $table->string('channel', 32);
            $table->string('recipient', 190);
            $table->string('provider', 64)->default('internal');
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('dedupe_key', 190)->unique();
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
