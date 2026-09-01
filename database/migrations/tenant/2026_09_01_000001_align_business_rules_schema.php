<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_rules')) {
            Schema::create('business_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 32)->default('string');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('business_rules', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_rules', 'key')) {
                $table->string('key', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('business_rules', 'value')) {
                $table->text('value')->nullable();
            }

            if (! Schema::hasColumn('business_rules', 'type')) {
                $table->string('type', 32)->default('string');
            }

            if (! Schema::hasColumn('business_rules', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('business_rules', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }

            // Older tenant databases may contain legacy columns that are NOT
            // part of the current BusinessRule model. They must remain data-safe
            // but cannot be required for current rule upserts.
            foreach (['name', 'conditions', 'actions'] as $legacyColumn) {
                if (Schema::hasColumn('business_rules', $legacyColumn)) {
                    $type = $legacyColumn === 'name' ? 'string' : 'text';
                    $table->{$type}($legacyColumn)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        // This migration aligns an existing application schema. Do not drop
        // the business_rules table or remove columns that may contain data.
    }
};
