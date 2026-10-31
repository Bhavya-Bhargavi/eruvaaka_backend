<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookPurchase extends Model
{
    protected $fillable = ['user_id', 'book_id', 'payment_order_id'];
}