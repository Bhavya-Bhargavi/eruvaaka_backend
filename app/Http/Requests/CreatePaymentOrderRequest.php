<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'userId' => ['nullable', 'integer'],
            'plan' => ['required', 'string', 'in:yearly,1year,3year,5year,emagazine_monthly,print_magazine_monthly,combo_monthly,emagazine_1year,emagazine_3year,emagazine_5year,print_magazine_1year,print_magazine_3year,print_magazine_5year,combo_1year,combo_3year,combo_5year'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['required_if:plan,print_magazine_monthly,combo_monthly,print_magazine_1year,print_magazine_3year,print_magazine_5year,combo_1year,combo_3year,combo_5year', 'nullable', 'string', 'max:2000'],
        ];
    }
}