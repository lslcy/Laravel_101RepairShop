<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $customer_id
 * @property string $customer_name
 * @property int|null $appliance_id
 * @property \Illuminate\Support\Carbon $date_in
 * @property string $status
 * @property string|null $dealer
 * @property \Illuminate\Support\Carbon|null $dop
 * @property \Illuminate\Support\Carbon|null $date_pulled_out
 * @property string|null $findings
 * @property string|null $remarks
 * @property array<array-key, mixed>|null $attachments
 * @property array<array-key, mixed>|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $deleted_by
 * @property string|null $deletion_reason
 * @property string|null $used_parts
 * @property-read \App\Models\Appliance|null $appliance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceProgressComment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\ServiceDetail|null $details
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Part> $parts
 * @property-read int|null $parts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereApplianceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDateIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDatePulledOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDealer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDeletionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereDop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereFindings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport whereUsedParts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceReport withoutTrashed()
 * @mixin \Eloquent
 */
class ServiceReport extends Model
{
    use SoftDeletes;

    /** 'In Progress' is used by the Flutter app; the rest by the web admin. */
    public const STATUSES = ['Pending', 'In Progress', 'Waiting for Parts', 'Under Repair', 'Completed', 'Cancelled'];

    /** Statuses grouped under the "In Progress" filter. */
    public const IN_PROGRESS_STATUSES = ['In Progress', 'Waiting for Parts', 'Under Repair'];

    protected $fillable = [
        'customer_id',
        'customer_name',
        'appliance_id',
        'date_in',
        'status',
        'dealer',
        'dop',
        'date_pulled_out',
        'findings',
        'remarks',
        'location',
        'used_parts',
        'attachments',
    ];

    protected $casts = [
        'location' => 'array',
        'attachments' => 'array',
        'date_in' => 'date',
        'dop' => 'date',
        'date_pulled_out' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appliance()
    {
        return $this->belongsTo(Appliance::class);
    }

    public function details()
    {
        return $this->hasOne(ServiceDetail::class , 'report_id');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class , 'report_id');
    }

    public function comments()
    {
        return $this->hasMany(ServiceProgressComment::class , 'report_id')->orderBy('created_at', 'asc');
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class)
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
