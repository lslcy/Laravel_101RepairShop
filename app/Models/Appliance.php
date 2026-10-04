<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $customer_id
 * @property string|null $brand
 * @property string|null $product
 * @property string|null $model_no
 * @property string|null $serial_no
 * @property string|null $date_in
 * @property string|null $warranty_end
 * @property string|null $category
 * @property string|null $status
 * @property string|null $appliance_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereApplianceSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereDateIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereModelNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereProduct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereSerialNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appliance whereWarrantyEnd($value)
 * @mixin \Eloquent
 */
class Appliance extends Model
{
    use SoftDeletes;

    /** Known status values; 'Active' and 'For Repair' come from the Flutter app. */
    public const STATUSES = ['Active', 'For Repair', 'Under Repair', 'Repaired', 'Inactive'];

    protected $fillable = [
        'customer_id',
        'brand',
        'product',
        'model_no',
        'serial_no',
        'date_in',
        'warranty_end',
        'category',
        'status',
        'appliance_size',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
