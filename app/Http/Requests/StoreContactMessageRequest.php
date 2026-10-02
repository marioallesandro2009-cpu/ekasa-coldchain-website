<?php

namespace App\Http\Requests;

use App\Support\Links;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation of the public contact form. The honeypot and the Turnstile check run BEFORE
 * this (App\Http\Middleware\GuardContactForm), so a bot never learns which rule it broke.
 * A plain POST goes back to the form with the errors in the "contact" bag; fetch gets JSON.
 */
class StoreContactMessageRequest extends FormRequest
{
    protected $errorBag = 'contact';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-().\s]{5,40}$/'],
            'company' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:80'],
            'service_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please enter your name.'),
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'phone.regex' => __('Please enter a valid phone number.'),
            'message.required' => __('Please write your message.'),
            'message.min' => __('Your message is too short.'),
            '*.max' => __('This field is too long.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return Links::section('contact');
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => __('Please check the form.'),
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
