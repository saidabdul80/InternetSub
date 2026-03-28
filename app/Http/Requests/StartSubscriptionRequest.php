<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_type' => ['required', 'integer', 'exists:plans,plan_type'],
            'gateway' => ['required', 'string', 'in:paystack,monnify'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\\+?\\d{8,20}$/'],
            'hotspot_return' => ['required', 'string', 'max:2048'],
            'hotspot_dst' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_type.required' => 'Please select a subscription plan.',
            'plan_type.exists' => 'The selected plan is invalid.',
            'gateway.required' => 'Please choose a payment gateway.',
            'gateway.in' => 'The selected payment gateway is invalid.',
            'phone_number.required' => 'Please enter your phone number.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'hotspot_return.required' => 'The hotspot return URL is required.',
        ];
    }
}
