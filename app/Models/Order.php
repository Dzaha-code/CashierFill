<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    protected $fillable = [
        'customer_id',
        'order_date',
        'status',
        'total_price',
        'discount',
        'discount_amount',
        'total_payment',
    ];
    public function orderdetail()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
