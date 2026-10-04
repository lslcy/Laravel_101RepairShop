<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aligns the shared Supabase schema so the Laravel admin and the Flutter app
 * can both write to it without breaking each other.
 *
 * 1. customers.deleted_by was created as uuid, but Laravel stores the staff
 *    user id (bigint) there, like every other table. Convert it to bigint.
 * 2. transactions has two "paid" columns: paid_at (written by Flutter) and
 *    payment_date (written by Laravel). Backfill both and add a trigger that
 *    keeps them in sync no matter which client writes.
 * 3. The same trigger fills transactions.customer_id (NOT NULL) from the
 *    linked service report when a client forgets to send it.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // --- 1. customers.deleted_by uuid -> bigint ---
        $type = DB::selectOne("
            select data_type from information_schema.columns
            where table_schema = 'public' and table_name = 'customers' and column_name = 'deleted_by'
        ");

        if ($type && $type->data_type === 'uuid') {
            // Any uuid value here cannot be a Laravel user id, so it is safe to drop.
            DB::statement('ALTER TABLE customers ALTER COLUMN deleted_by TYPE bigint USING NULL');
        }

        // --- 2. Backfill paid_at <-> payment_date ---
        DB::statement("
            UPDATE transactions
            SET payment_date = (paid_at AT TIME ZONE 'Asia/Manila')::date
            WHERE payment_date IS NULL AND paid_at IS NOT NULL
        ");
        DB::statement("
            UPDATE transactions
            SET paid_at = (payment_date::timestamp AT TIME ZONE 'Asia/Manila')
            WHERE paid_at IS NULL AND payment_date IS NOT NULL
        ");

        // --- 3. Sync trigger ---
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.sync_transaction_fields()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NEW.customer_id IS NULL AND NEW.report_id IS NOT NULL THEN
                    SELECT customer_id INTO NEW.customer_id
                    FROM public.service_reports
                    WHERE id = NEW.report_id;
                END IF;

                IF NEW.paid_at IS NOT NULL AND NEW.payment_date IS NULL THEN
                    NEW.payment_date := (NEW.paid_at AT TIME ZONE 'Asia/Manila')::date;
                ELSIF NEW.payment_date IS NOT NULL AND NEW.paid_at IS NULL THEN
                    NEW.paid_at := (NEW.payment_date::timestamp AT TIME ZONE 'Asia/Manila');
                END IF;

                RETURN NEW;
            END;
            $$;

            DROP TRIGGER IF EXISTS trg_sync_transaction_fields ON public.transactions;

            CREATE TRIGGER trg_sync_transaction_fields
            BEFORE INSERT OR UPDATE ON public.transactions
            FOR EACH ROW EXECUTE FUNCTION public.sync_transaction_fields();
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_transaction_fields ON public.transactions;');
        DB::unprepared('DROP FUNCTION IF EXISTS public.sync_transaction_fields();');
        // customers.deleted_by is intentionally left as bigint.
    }
};
