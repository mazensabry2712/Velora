<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `resources` and `service_resources` tables.
 * Resources are physical assets required for a service (rooms, chairs, equipment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->json('name');                                        // {"en":"Room 1","ar":"غرفة 1",...}
            $table->string('type', 30)->default('room')
                  ->comment('room|chair|equipment|vehicle|other');
            $table->tinyInteger('quantity')->default(1)
                  ->comment('How many of this resource exist');
            $table->string('color', 7)->nullable()
                  ->comment('Calendar display color hex');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('service_resources', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->tinyInteger('quantity')->default(1)
                  ->comment('How many of this resource the service consumes');

            $table->primary(['service_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_resources');
        Schema::dropIfExists('resources');
    }
};
