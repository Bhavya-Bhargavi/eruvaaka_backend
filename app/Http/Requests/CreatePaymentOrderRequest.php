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
            'plan' => ['required', 'string', 'in:yearly,1year,3year,5year,emagazine_monthly,print_magazine_monthly,combo_monthly'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['required_if:plan,print_magazine_monthly,combo_monthly', 'nullable', 'string', 'max:2000'],
        ];
    }
}