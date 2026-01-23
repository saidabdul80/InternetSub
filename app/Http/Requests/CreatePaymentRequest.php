<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_type' => ['required', 'integer', 'exists:plans,plan_type'],
            // 'url' => ['required', 'string', 'max:2048', 'url'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\\+?\\d{8,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_type.required' => 'Please select a plan.',
            'plan_type.exists' => 'The selected plan is invalid.',
            'url.required' => 'The access point URL is required.',
            'url.url' => 'The access point URL must be a valid URL.',
            'email.email' => 'Please provide a valid email address.',
            'phone_number.required' => 'Please provide a phone number.',
            'phone_number.regex' => 'Please provide a valid phone number.',
        ];
    }
}
