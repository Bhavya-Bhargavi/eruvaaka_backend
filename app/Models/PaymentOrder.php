<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    protected $table = 'payment_orders';
    protected $fillable = ['user_id', 'razorpay_order_id', 'razorpay_payment_id', 'amount', 'currency', 'receipt', 'plan_type', 'shipping_address', 'signature', 'status', 'coupon_id', 'discount_amount'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
