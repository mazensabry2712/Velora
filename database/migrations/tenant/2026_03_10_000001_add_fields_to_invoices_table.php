<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Unique invoice number (e.g. INV-2026-00001)
            $table->string('number')->nullable()->unique()->after('id');

            // Link to appointment (optional)
            $table->unsignedBigInteger('appointment_id')->nullable()->after('customer_id');
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();

            // Tax rate as percentage (e.g. 15.00 = 15%)
            $table->decimal('tax_rate', 5, 2)->default(0)->after('amount');

            // Optional notes
            $table->text('notes')->nullable()->after('tax_rate');

            // Due date
            $table->date('due_date')->nullable()->after('notes');

            // Issued at timestamp
            $table->timestamp('issued_at')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn(['number', 'appointment_id', 'tax_rate', 'notes', 'due_date', 'issued_at']);
        });
    }
};
