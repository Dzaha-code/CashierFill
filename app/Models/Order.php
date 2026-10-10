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
        'total_price',
        'discount',
        'discount_amount',
        'total_payment',
        'status',
        'payment_method',
        'payment_status',
    ];

    protected $attributes = [
        'order_date' => null,
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_date)) {
                $order->order_date = now()->toDateString();
            }
        });
    }
    public function orderdetail()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
