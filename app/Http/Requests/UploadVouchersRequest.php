<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVouchersRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:csv,txt,json', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a voucher file.',
            'file.mimes' => 'Upload a CSV, JSON, or TXT file.',
        ];
    }
}
