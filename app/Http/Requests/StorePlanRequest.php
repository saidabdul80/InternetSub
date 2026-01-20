<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'price_kobo' => ['required', 'integer', 'min:100'],
            'speed_mbps' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'mikrotik_profile' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a plan name.',
            'price_kobo.required' => 'Please provide the plan price.',
            'speed_mbps.required' => 'Please provide the plan speed.',
            'duration_minutes.required' => 'Please provide the plan duration.',
            'mikrotik_profile.required' => 'Please provide a MikroTik profile name.',
        ];
    }
}
