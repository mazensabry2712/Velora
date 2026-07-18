<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_settings', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->string('country_name', 100);
            $table->string('default_language', 10)->default('en');
            $table->string('default_currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('country_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_settings');
    }
};
// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         Schema::create('country_settings', function (Blueprint $table) {
//             $table->id();
//             $table->string('country_code', 2)->unique(); // ISO 3166-1 alpha-2
//             $table->string('country_name', 100);
//             $table->string('default_language', 10)->default('en');
//             $table->string('default_currency', 3)->default('USD');
//             $table->boolean('is_active')->default(true);
//             $table->timestamps();

//             $table->index('country_code');
//             $table->index('is_active');
//         });
//     }

//     public function down(): void
//     {
//         Schema::dropIfExists('country_settings');
//     }
// };
