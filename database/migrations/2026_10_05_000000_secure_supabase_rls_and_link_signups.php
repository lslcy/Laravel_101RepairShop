<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Locks down the shared Supabase database for the Flutter app.
 *
 * Laravel connects as `postgres` (BYPASSRLS), so none of this affects the admin site.
 * It only changes what the public anon key / logged-in mobile customers can reach
 * through Supabase's REST API.
 *
 * 1. Staff-only Laravel tables: RLS on, no policies -> invisible to anon/authenticated.
 * 2. Repair detail tables: RLS on + customers may read rows for their own reports.
 * 3. Reference tables (parts, service_prices): RLS on + read-only for logged-in customers.
 * 4. handle_new_user(): link a sign-up to an existing counter-created customer (same email)
 *    instead of always inserting a duplicate.
 */
return new class extends Migration
{
    /** Tables only Laravel should touch. */
    private array $staffOnly = [
        'users', 'sessions', 'password_reset_tokens', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'migrations', 'notifications', 'staff_comments',
    ];

    /** table => column holding the service_reports id */
    private array $ownedViaReport = [
        'service_details' => 'report_id',
        'part_service_report' => 'service_report_id',
        'service_progress_comments' => 'report_id',
    ];

    private array $readOnlyReference = ['parts', 'service_prices'];

    private const OWN_REPORTS = 'select sr.id from public.service_reports sr join public.customers c on c.id = sr.customer_id where c.auth_id = auth.uid()';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->staffOnly as $t) {
            if ($this->exists($t)) {
                DB::statement("alter table public.\"$t\" enable row level security");
            }
        }

        foreach ($this->ownedViaReport as $t => $col) {
            if (!$this->exists($t)) continue;
            DB::statement("alter table public.\"$t\" enable row level security");
            DB::statement("drop policy if exists \"Customers can view own $t\" on public.\"$t\"");
            DB::statement("create policy \"Customers can view own $t\" on public.\"$t\" for select to authenticated using ($col in (" . self::OWN_REPORTS . '))');
        }

        foreach ($this->readOnlyReference as $t) {
            if (!$this->exists($t)) continue;
            DB::statement("alter table public.\"$t\" enable row level security");
            DB::statement("drop policy if exists \"Authenticated can read $t\" on public.\"$t\"");
            $cond = \Illuminate\Support\Facades\Schema::hasColumn($t, 'deleted_at') ? 'deleted_at is null' : 'true';
            DB::statement("create policy \"Authenticated can read $t\" on public.\"$t\" for select to authenticated using ($cond)");
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $function$
DECLARE
  existing_id uuid;
BEGIN
  -- Customer first registered at the counter? Link it instead of creating a duplicate.
  IF NEW.email IS NOT NULL THEN
    SELECT id INTO existing_id
      FROM public.customers
     WHERE auth_id IS NULL
       AND deleted_at IS NULL
       AND lower(email) = lower(NEW.email)
     ORDER BY created_at
     LIMIT 1
     FOR UPDATE;
  END IF;

  IF existing_id IS NOT NULL THEN
    UPDATE public.customers
       SET auth_id    = NEW.id,
           first_name = COALESCE(NULLIF(first_name, ''), NEW.raw_user_meta_data ->> 'first_name'),
           last_name  = COALESCE(NULLIF(last_name, ''),  NEW.raw_user_meta_data ->> 'last_name'),
           phone_no   = COALESCE(NULLIF(phone_no, ''),   NEW.raw_user_meta_data ->> 'phone_no'),
           updated_at = now()
     WHERE id = existing_id;
  ELSE
    INSERT INTO public.customers (auth_id, email, first_name, last_name, phone_no)
    VALUES (
      NEW.id,
      NEW.email,
      NEW.raw_user_meta_data ->> 'first_name',
      NEW.raw_user_meta_data ->> 'last_name',
      NEW.raw_user_meta_data ->> 'phone_no'
    );
  END IF;

  RETURN NEW;
END;
$function$;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->ownedViaReport as $t => $col) {
            if (!$this->exists($t)) continue;
            DB::statement("drop policy if exists \"Customers can view own $t\" on public.\"$t\"");
            DB::statement("alter table public.\"$t\" disable row level security");
        }
        foreach ($this->readOnlyReference as $t) {
            if (!$this->exists($t)) continue;
            DB::statement("drop policy if exists \"Authenticated can read $t\" on public.\"$t\"");
            DB::statement("alter table public.\"$t\" disable row level security");
        }
        foreach ($this->staffOnly as $t) {
            if ($this->exists($t)) {
                DB::statement("alter table public.\"$t\" disable row level security");
            }
        }

        // Original trigger function (always inserts).
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
AS $function$
BEGIN
  INSERT INTO public.customers (auth_id, email, first_name, last_name, phone_no)
  VALUES (
    NEW.id,
    NEW.email,
    NEW.raw_user_meta_data ->> 'first_name',
    NEW.raw_user_meta_data ->> 'last_name',
    NEW.raw_user_meta_data ->> 'phone_no'
  );
  RETURN NEW;
END;
$function$;
SQL);
    }

    private function exists(string $table): bool
    {
        return DB::selectOne('select to_regclass(?) as t', ["public.\"$table\""])->t !== null;
    }
};
