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
        Schema::create('upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('current_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('requested_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->string('requested_by_name');
            $table->string('requested_by_email');
            $table->text('message')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upgrade_requests');
    }
};
