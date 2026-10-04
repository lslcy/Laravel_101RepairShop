<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Appointment booked by a customer from the Flutter mobile app.
 * The table lives in Supabase and is owned by the mobile app.
 *
 * @property int $id
 * @property string $customer_id
 * @property string $title
 * @property string|null $appliance_name
 * @property \Illuminate\Support\Carbon $appointment_date
 * @property string|null $time_slot
 * @property string|null $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer|null $customer
 * @mixin \Eloquent
 */
class Appointment extends Model
{
    public const STATUSES = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

    protected $fillable = [
        'customer_id',
        'title',
        'appliance_name',
        'appointment_date',
        'time_slot',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
