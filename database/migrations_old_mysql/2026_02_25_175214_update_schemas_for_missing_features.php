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
        // Customers: Add email
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('last_name');
        });

        // Parts: Rename 'description' to 'name'
        Schema::table('parts', function (Blueprint $table) {
            $table->renameColumn('description', 'name');
        });

        // Service Reports: Replace appliance_name with appliance_id
        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropColumn('appliance_name');
            $table->foreignId('appliance_id')->nullable()->after('customer_name')->constrained('appliances')->nullOnDelete();
        });

        // Users (Staff): Replace full_name with first_name and last_name
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->renameColumn('name', 'description');
        });

        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropForeign(['appliance_id']);
            $table->dropColumn('appliance_id');
            $table->string('appliance_name')->nullable()->after('customer_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
            $table->string('full_name')->after('id');
        });
    }
};
