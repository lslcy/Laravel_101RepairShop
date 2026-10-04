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
        Schema::create('service_details', function (Blueprint $table) {
            $table->id(); // detail_id
            $table->foreignId('report_id')->constrained('service_reports')->onDelete('cascade');
            $table->json('service_types');
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->date('date_repaired')->nullable();
            $table->date('date_delivered')->nullable();
            $table->text('complaint')->nullable();
            $table->decimal('labor', 10, 2)->default(0);
            $table->decimal('pullout_delivery', 10, 2)->default(0);
            $table->decimal('parts_total_charge', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            // Linking to users table? Or just string names as per original?
            // Original schema used names/strings like 'ok (Technician)'
            $table->string('receptionist')->nullable();
            $table->string('manager')->nullable();
            $table->string('technician')->nullable();
            $table->string('released_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_details');
    }
};
