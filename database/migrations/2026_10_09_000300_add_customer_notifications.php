<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The bundled Supabase SQL owns its BEGIN/COMMIT transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::unprepared(file_get_contents(database_path('sql/202610090006_customer_notifications.sql')));
    }

    public function down(): void
    {
        // Preserve the customer inbox. Removing message history requires
        // deliberate administrative retention handling.
    }
};
