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
        Schema::create('service_progress_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('service_reports')->onDelete('cascade');
            $table->string('progress_key');
            $table->longText('comment_text');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_progress_comments');
    }
};
