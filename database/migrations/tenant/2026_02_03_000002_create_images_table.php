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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Original filename
            $table->string('filename'); // Stored filename
            $table->string('path'); // Full path relative to storage
            $table->string('folder'); // Folder name (avatars, services, etc.)
            $table->string('disk')->default('public'); // Storage disk
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // File size in bytes
            $table->morphs('imageable'); // Polymorphic relation (imageable_id, imageable_type)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
