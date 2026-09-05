<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'start_date', 'end_date', 'plan_type', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'datetime', 'end_date' => 'datetime'];
    }
}