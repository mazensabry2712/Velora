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
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('message');

            $table->enum('type', [
                'info',
                'success',
                'warning',
                'danger',
            ])->default('info');

            $table->enum('target', [
                'all',
                'specific',
            ])->default('all');

            // قائمة الـ Tenants المستهدفة
            $table->json('tenant_ids')->nullable();

            // جدولة الإرسال
            $table->timestamp('scheduled_at')->nullable();

            // حالة الإرسال
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            // معرف الأدمن الذي أنشأ الإشعار (بدون Foreign Key)
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('created_by');
            $table->index('is_sent');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
//         Schema::create('system_notifications', function (Blueprint $table) {
//             $table->id();
//             $table->string('title');
//             $table->text('message');
//             $table->enum('type', ['info', 'success', 'warning', 'danger'])->default('info');
//             $table->enum('target', ['all', 'specific'])->default('all');
//             $table->json('tenant_ids')->nullable(); // if target is 'specific'
//             $table->timestamp('scheduled_at')->nullable();
//             $table->boolean('is_sent')->default(false);
//             $table->timestamp('sent_at')->nullable();
//             $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
//             $table->timestamps();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('system_notifications');
//     }
// };
