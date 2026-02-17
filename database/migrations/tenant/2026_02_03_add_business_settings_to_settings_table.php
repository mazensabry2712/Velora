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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('id');
            $table->string('business_name')->nullable()->after('tenant_id');
            $table->string('business_name_ar')->nullable()->after('business_name');
            $table->string('phone', 50)->nullable()->after('business_name_ar');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('logo')->nullable()->after('address');
            $table->string('whatsapp', 50)->nullable()->after('logo');
            $table->string('facebook')->nullable()->after('whatsapp');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('twitter')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('twitter');
            $table->string('snapchat', 100)->nullable()->after('tiktok');

            // Add unique index on tenant_id
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['tenant_id']);
            $table->dropColumn([
                'tenant_id',
                'business_name',
                'business_name_ar',
                'phone',
                'email',
                'address',
                'logo',
                'whatsapp',
                'facebook',
                'instagram',
                'twitter',
                'tiktok',
                'snapchat',
            ]);
        });
    }
};
