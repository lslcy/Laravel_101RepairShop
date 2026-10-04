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
        Schema::create('service_reports', function (Blueprint $table) {
            $table->id(); // report_id
            // Legacy fields to match original schema
            $table->string('customer_name');
            $table->string('appliance_name');
            $table->date('date_in');
            $table->string('status')->default('Pending');
            $table->string('dealer')->nullable();
            $table->date('dop')->nullable(); // date of purchase
            $table->date('date_pulled_out')->nullable();
            $table->text('findings')->nullable();
            $table->text('remarks')->nullable();
            $table->json('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
