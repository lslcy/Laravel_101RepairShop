<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $report_id
 * @property string $customer_id
 * @property \Illuminate\Support\Carbon|null $paid_at Set by the Flutter app; kept in sync with payment_date
 * @property numeric|null $parts_total
 * @property numeric|null $labor_total
 * @property numeric|null $total_amount
 * @property string|null $payment_status
 * @property \Illuminate\Support\Carbon|null $payment_date
 * @property string|null $received_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $deleted_by
 * @property string|null $deletion_reason
 * @property string|null $paymongo_link_id
 * @property string|null $payment_url
 * @property-read \App\Models\ServiceReport|null $report
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDeletionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereLaborTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePartsTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePaymentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePaymongoLinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction withoutTrashed()
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use SoftDeletes;

    /** 'Pending' is written by the Flutter app and treated like 'Unpaid'. */
    public const STATUSES = ['Paid', 'Unpaid', 'Partial', 'Pending'];

    protected $fillable = [
        'report_id',
        'customer_id',
        'parts_total',
        'labor_total',
        'total_amount',
        'partial_payment_amount',
        'payment_status',
        'payment_method',
        'reference_no',
        'payment_date',
        'paid_at',
        'payment_due',
        'received_by',
        'paymongo_link_id',
        'payment_url'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_due' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Transaction $transaction) {
            // transactions.customer_id is NOT NULL in Supabase.
            if (empty($transaction->customer_id) && $transaction->report_id) {
                $transaction->customer_id = ServiceReport::withTrashed()
                    ->whereKey($transaction->report_id)
                    ->value('customer_id');
            }

            // Keep the Flutter (paid_at) and Laravel (payment_date) columns aligned.
            if ($transaction->isDirty('payment_date') && $transaction->payment_date) {
                if (!$transaction->paid_at || !$transaction->paid_at->isSameDay($transaction->payment_date)) {
                    $transaction->paid_at = $transaction->payment_date->copy()->setTimeFrom(now());
                }
            } elseif ($transaction->isDirty('paid_at') && $transaction->paid_at) {
                $transaction->payment_date = $transaction->paid_at->copy()->startOfDay();
            }
        });
    }

    public function report()
    {
        return $this->belongsTo(ServiceReport::class , 'report_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
