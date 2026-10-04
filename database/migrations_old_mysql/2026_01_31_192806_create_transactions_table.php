<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // transaction_id
            $table->foreignId('report_id')->nullable()->constrained('service_reports')->onDelete('cascade');
            $table->decimal('parts_total', 10, 2)->nullable();
            $table->decimal('labor_total', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('received_by')->nullable(); // Could be FK to users, but legacy used generated string
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
