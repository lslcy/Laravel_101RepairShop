<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CustomerPaymentSubmission extends Model
{
    protected $table = 'customer_payment_submissions';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        abort_unless(Schema::hasTable($this->getTable()), 503, 'Customer payment review is not available yet.');

        return parent::resolveRouteBinding($value, $field);
    }
}
