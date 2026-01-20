<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
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
        $phone = $this->string('login')->trim()->toString();
        $userExists = $phone !== '' && User::query()->where('phone', $phone)->exists();
        $loginRules = $this->user() === null
            ? ['required', 'string', 'max:255', 'regex:/^(?:\\+234|0)(7|8|9)\\d{9}$/']
            : ['nullable', 'string', 'max:255', 'regex:/^(?:\\+234|0)(7|8|9)\\d{9}$/'];

        return [
            'login' => $loginRules,
            'password' => [
                $this->user() === null && ! $userExists ? 'required' : 'nullable',
                'string',
                'size:4',
                'alpha_num',
            ],
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
            'login.required' => 'Please provide a phone number.',
            'login.regex' => 'Please provide a valid Nigerian phone number.',
            'password.required' => 'Please select one of the suggested passcodes.',
            'password.size' => 'Passcode must be exactly 4 characters.',
            'password.alpha_num' => 'Passcode must use only letters and numbers.',
        ];
    }
}
