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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Store the tenant user ID without a foreign key
            $table->unsignedBigInteger('user_id')->nullable();

            // Tenant identifier
            $table->string('tenant_id')->nullable();

            // Action performed
            $table->string('action');

            // Model information
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();

            // Human-readable description
            $table->text('description');

            // Additional data (old/new values, metadata, etc.)
            $table->json('properties')->nullable();

            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('action');
            $table->index('model_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
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
//         Schema::create('activity_logs', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
//             $table->string('tenant_id')->nullable(); // for tenant-specific actions
//             $table->string('action'); // created, updated, deleted, logged_in, etc.
//             $table->string('model_type')->nullable(); // Tenant, User, etc.
//             $table->string('model_id')->nullable();
//             $table->text('description');
//             $table->json('properties')->nullable(); // old/new values
//             $table->string('ip_address', 45)->nullable();
//             $table->text('user_agent')->nullable();
//             $table->timestamps();

//             $table->index(['user_id', 'created_at']);
//             $table->index(['tenant_id', 'created_at']);
//             $table->index('action');
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('activity_logs');
//     }
// };
