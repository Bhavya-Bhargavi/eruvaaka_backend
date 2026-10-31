<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponValidationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'coupon_code' => $this->input('coupon_code', $this->input('couponCode', $this->input('coupon'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:100'],
            'plan' => ['required_without:book_id', 'prohibits:book_id', 'nullable', 'string', 'in:yearly,1year,3year,5year,emagazine_monthly,print_magazine_monthly,combo_monthly,emagazine_1year,emagazine_3year,emagazine_5year,print_magazine_1year,print_magazine_3year,print_magazine_5year,combo_1year,combo_3year,combo_5year'],
            'book_id' => ['required_without:plan', 'prohibits:plan', 'nullable', 'integer', 'exists:books,id'],
        ];
    }
}