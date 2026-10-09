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
        DB::unprepared(file_get_contents(database_path('sql/202610090004_appointment_reminders.sql')));
    }

    public function down(): void
    {
        // Preserve appointment preferences and payment evidence on rollback.
        // These records require deliberate administrative retention handling.
    }
};