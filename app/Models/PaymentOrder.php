<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    protected $table = 'payment_orders';
    protected $fillable = ['user_id', 'razorpay_order_id', 'razorpay_payment_id', 'amount', 'currency', 'receipt', 'plan_type', 'signature', 'status'];
}
