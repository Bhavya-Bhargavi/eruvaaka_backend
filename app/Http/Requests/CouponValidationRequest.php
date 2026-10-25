<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:100'],
            'plan' => ['required', 'string', 'in:yearly,1year,3year,5year,emagazine_monthly,print_magazine_monthly,combo_monthly'],
        ];
    }
}