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
            'plan' => ['required', 'string', 'in:emagazine_1year,emagazine_3year,emagazine_5year,print_magazine_1year,print_magazine_3year,print_magazine_5year,combo_1year,combo_3year,combo_5year'],
        ];
    }
}