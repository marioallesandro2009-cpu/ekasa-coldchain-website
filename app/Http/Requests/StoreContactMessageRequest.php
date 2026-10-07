<?php

namespace App\Http\Requests;

use App\Support\LeadOptions;
use App\Support\Links;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-().\s]{5,40}$/'],
            'company' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', Rule::in(array_keys(LeadOptions::categories()))],
            'quantity_tons' => ['nullable', 'string', 'max:20', fn (string $attribute, mixed $value, \Closure $fail) => self::validTons($value) ?: $fail(__('Please enter the quantity in tons, for example 12.5.'))],
            'container_size' => ['nullable', 'string', Rule::in(array_keys(LeadOptions::containers()))],
            'destination_country' => ['nullable', 'string', 'max:80'],
            'service_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /**
     * A positive number of tons, with a decimal point or comma (up to 100,000 and 2 decimals).
     */
    private static function validTons(mixed $value): bool
    {
        $tons = LeadOptions::parseTons($value);

        return $tons !== null && $tons > 0 && $tons <= 100000;
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
            'category.in' => __('Please choose one of the categories.'),
            'container_size.in' => __('Please choose 20 feet or 40 feet.'),
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
