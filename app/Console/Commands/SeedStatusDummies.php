<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ServiceReport;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SeedStatusDummies extends Command
{
    protected $signature = 'repairshop:seed-status-dummies {customer : Exact existing email, phone or customer ID} {--scope=all : all, repairs, appointments or payments} {--date= : Base date in Manila (YYYY-MM-DD)} {--dry-run : Preview without inserting records}';

    protected $description = 'Add clearly marked, repeat-safe customer examples for each repair, appointment and financial status';

    private array $created;

    private array $retained;

    public function handle(): int
    {
        $scope = (string) $this->option('scope');
        if (! in_array($scope, ['all', 'repairs', 'appointments', 'payments'], true)) {
            $this->error('Scope must be all, repairs, appointments or payments.');

            return self::FAILURE;
        }
        $date = $this->option('date') ?: CarbonImmutable::now('Asia/Manila')->toDateString();
        if (! is_string($date) || ! preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            $this->error('Date must be a valid YYYY-MM-DD date in Manila.');

            return self::FAILURE;
        }
        $day = CarbonImmutable::parse($date, 'Asia/Manila')->startOfDay();
        $this->created = $this->retained = array_fill_keys(['appliances', 'service_reports', 'appointments', 'transactions'], 0);
        try {
            DB::transaction(function () use ($scope, $day) {
                $customer = $this->customer();
                if ($this->option('dry-run')) {
                    $this->info('Preview only: no records will be inserted.');
                    $this->line('Repairs: '.(in_array($scope, ['all', 'repairs'], true) ? implode(', ', ServiceReport::STATUSES) : 'not selected'));
                    $this->line('Appointments: '.(in_array($scope, ['all', 'appointments'], true) ? implode(', ', Appointment::STATUSES).', past Pending' : 'not selected'));
                    $this->line('Payments: '.(in_array($scope, ['all', 'payments'], true) ? 'Paid, Unpaid, Partial' : 'not selected'));
                    $this->line('Base date: '.$day->toDateString().' (Asia/Manila). No accounts or provider requests.');

                    return;
                }
                if (in_array($scope, ['all', 'repairs'], true)) {
                    $products = ['Electric fan', 'Refrigerator', 'Washing machine', 'Air conditioner', 'Rice cooker', 'Microwave'];
                    foreach (ServiceReport::STATUSES as $index => $status) {
                        $marker = $this->marker($customer->id, 'repair', $status);
                        $this->report($customer, $marker, $products[$index], $status, $day);
                    }
                }
                if (in_array($scope, ['all', 'appointments'], true)) {
                    foreach (Appointment::STATUSES as $index => $status) {
                        $offset = in_array($status, ['Completed', 'Cancelled'], true) ? -2 - $index : 2 + $index;
                        $this->appointment($customer, $status, $status, $day->addDays($offset));
                    }
                    $this->appointment($customer, 'Past awaiting staff', 'Pending', $day->subDays(1));
                }
                if (in_array($scope, ['all', 'payments'], true)) {
                    foreach (['Paid', 'Unpaid', 'Partial'] as $status) {
                        $marker = $this->marker($customer->id, 'payment', $status);
                        $report = $this->report($customer, $marker, $status.' payment example', 'Completed', $day);
                        $existing = $this->existing('transactions', 'reference_no', $marker, $customer->id);
                        if ($existing) {
                            continue;
                        }
                        if (DB::table('transactions')->where('report_id', $report)->exists()) {
                            throw new RuntimeException('A demo bill was edited. Existing payments were left untouched.');
                        }
                        $paid = $status === 'Paid';
                        $this->insert('transactions', [
                            'report_id' => $report,
                            'customer_id' => $customer->id,
                            'parts_total' => 400,
                            'labor_total' => 600,
                            'total_amount' => 1000,
                            'partial_payment_amount' => $status === 'Partial' ? 400 : null,
                            'payment_status' => $status,
                            'payment_method' => $status === 'Unpaid' ? null : 'Cash',
                            'paid_at' => $paid ? $day->subDay()->setTime(10, 0)->utc()->toIso8601String() : null,
                            'payment_date' => $paid ? $day->subDay()->toDateString() : null,
                            'payment_due' => $paid ? null : $day->addDays(7)->toDateString(),
                            'received_by' => $status === 'Unpaid' ? null : '[DEMO] No actual money received',
                            'reference_no' => $marker,
                            'paymongo_link_id' => null,
                            'payment_url' => null,
                        ]);
                    }
                }
            }, 3);
        } catch (RuntimeException $error) {
            if ($error instanceof QueryException) {
                $this->error('No dummy records were saved. A database constraint or permission prevented setup.');
            } else {
                $this->error($error->getMessage());
            }

            return self::FAILURE;
        }
        if (! $this->option('dry-run')) {
            $this->table(['Record', 'Added', 'Kept unchanged'], collect($this->created)->map(fn ($count, $table) => [$table, $count, $this->retained[$table]])->values()->all());
            $this->line('All samples are marked [DEMO]. GCash pending review requires a real uploaded receipt and is not fabricated.');
        }

        return self::SUCCESS;
    }

    private function customer(): object
    {
        $identifier = trim((string) $this->argument('customer'));
        if ($identifier === '') {
            throw new RuntimeException('Enter an exact existing customer email, phone or ID.');
        }
        $matches = DB::table('customers')->where(function ($query) use ($identifier) {
            $query->whereRaw('cast(id as text) = ?', [$identifier])
                ->orWhereRaw('lower(trim(email)) = ?', [mb_strtolower($identifier)])
                ->orWhere('phone_no', $identifier);
        })->lockForUpdate()->limit(2)->get();
        if ($matches->count() !== 1) {
            throw new RuntimeException('The identifier must match exactly one existing customer.');
        }
        $customer = $matches->first();
        if ($customer->deleted_at !== null) {
            throw new RuntimeException('The customer is archived. No records were added.');
        }
        if (! Str::isUuid($customer->auth_id ?? '')) {
            throw new RuntimeException('The customer needs a linked sign-in account before adding app examples.');
        }
        if (DB::table('customers')->where('auth_id', $customer->auth_id)->count() !== 1) {
            throw new RuntimeException('The sign-in account is linked to multiple customers. No records were added.');
        }
        if (DB::getDriverName() === 'pgsql' && ! DB::table('auth.users')->where('id', $customer->auth_id)->whereNull('deleted_at')->where(function ($query) {
            $query->whereNull('banned_until')->orWhere('banned_until', '<=', CarbonImmutable::now()->utc());
        })->exists()) {
            throw new RuntimeException('The linked sign-in account is unavailable. No records were added.');
        }

        return $customer;
    }

    private function marker(string $customerId, string $kind, string $status): string
    {
        return 'DEMO-STATUS-v1:'.$customerId.':'.$kind.':'.Str::slug($status);
    }

    private function existing(string $table, string $column, string $marker, string $customerId): ?object
    {
        $matches = DB::table($table)->where($column, $marker)->limit(2)->get();
        if ($matches->count() > 1) {
            throw new RuntimeException('A duplicate demo marker exists. No records were added.');
        }
        $row = $matches->first();
        if (! $row) {
            return null;
        }
        if ((string) $row->customer_id !== $customerId || ($row->deleted_at ?? null) !== null) {
            throw new RuntimeException('A demo marker belongs to an archived or different customer record. No records were added.');
        }
        $this->retained[$table]++;

        return $row;
    }

    private function insert(string $table, array $attributes): int
    {
        $timestamp = CarbonImmutable::now()->utc()->toIso8601String();
        $id = DB::table($table)->insertGetId($attributes + ['created_at' => $timestamp, 'updated_at' => $timestamp]);
        $this->created[$table]++;

        return (int) $id;
    }

    private function report(object $customer, string $marker, string $product, string $status, CarbonImmutable $day): int
    {
        $appliance = $this->existing('appliances', 'serial_no', $marker, $customer->id);
        $applianceId = $appliance?->id ?? $this->insert('appliances', [
            'customer_id' => $customer->id,
            'brand' => '[DEMO]',
            'product' => '[DEMO] '.$product,
            'model_no' => 'STATUS-EXAMPLE',
            'serial_no' => $marker,
            'date_in' => $day->subDays(5)->utc()->toIso8601String(),
            'category' => 'Demo',
            'status' => match ($status) {
                'Completed' => 'Repaired', 'Cancelled' => 'Inactive', 'Pending' => 'For Repair', default => 'Under Repair'
            },
            'appliance_size' => 'Small',
            'warranty_end' => null,
        ]);
        $report = $this->existing('service_reports', 'remarks', $marker, $customer->id);
        if ($report) {
            if ((int) $report->appliance_id !== (int) $applianceId) {
                throw new RuntimeException('A demo repair was reassigned. Existing records were left untouched.');
            }

            return (int) $report->id;
        }
        if (DB::table('service_reports')->where('appliance_id', $applianceId)->exists()) {
            throw new RuntimeException('A demo repair was edited. Existing records were left untouched.');
        }

        return $this->insert('service_reports', [
            'customer_id' => $customer->id,
            'customer_name' => trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')),
            'appliance_id' => $applianceId,
            'date_in' => $day->subDays(5)->utc()->toIso8601String(),
            'status' => $status,
            'findings' => '[DEMO] '.$status.' example. No physical appliance, real repair or payment.',
            'remarks' => $marker,
            'attachments' => '[]',
            'location' => '{}',
        ]);
    }

    private function appointment(object $customer, string $label, string $status, CarbonImmutable $day): void
    {
        $marker = $this->marker($customer->id, 'appointment', $label);
        if ($this->existing('appointments', 'notes', $marker, $customer->id)) {
            return;
        }
        $attributes = [
            'customer_id' => $customer->id,
            'title' => '[DEMO] '.$label.' appointment',
            'appliance_name' => '[DEMO] Electric fan',
            'appointment_date' => $day->setTime(9, 0)->utc()->toIso8601String(),
            'time_slot' => '9:00 AM - 10:00 AM',
            'status' => $status,
            'notes' => $marker,
        ];
        if (Schema::hasColumn('appointments', 'reminder_minutes')) {
            $attributes['reminder_minutes'] = null;
        }
        $this->insert('appointments', $attributes);
    }
}
