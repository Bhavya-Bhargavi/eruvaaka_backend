<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $fillable = ['first_name', 'last_name', 'phone', 'email', 'password_hash', 'state', 'district', 'mandal', 'pincode', 'address', 'designation', 'designation_other', 'nationality', 'crop_interests', 'otp_code', 'otp_expires_at', 'role', 'is_active', 'subscription_status'];
    protected $hidden = ['password_hash', 'otp_code'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'otp_expires_at' => 'datetime',
            'designation' => 'array',
            'crop_interests' => 'array',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}
