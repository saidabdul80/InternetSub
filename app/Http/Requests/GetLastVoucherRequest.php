<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLastVoucherRequest extends FormRequest
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
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\\+?\\d{8,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'Please provide your phone number.',
            'phone_number.regex' => 'Please provide a valid phone number.',
        ];
    }
}
