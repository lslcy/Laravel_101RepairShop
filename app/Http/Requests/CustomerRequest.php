<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Support\CustomerIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['Administrator', 'Secretary'], true);
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['first_name', 'last_name'] as $field) {
            if (is_string($this->input($field))) {
                $data[$field] = CustomerIdentity::cleanName($this->input($field));
            }
        }
        if (is_string($this->input('email'))) {
            $data['email'] = CustomerIdentity::emailKey($this->input('email'));
        }
        $phone = $this->input('phone_no');
        if (is_string($phone) && preg_match('/^\+?[0-9().\s-]+$/D', $phone)) {
            $data['phone_no'] = CustomerIdentity::phoneKey($phone);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customer = $customer instanceof Customer ? $customer : null;
        $nameRules = ['bail', 'required', 'string', 'max:255', "regex:/\A(?=.*\\p{L})[\\p{L}\\p{M} .'\\x{2019}-]+\\z/u"];

        $rules = [
            'first_name' => $nameRules,
            'last_name' => [...$nameRules, function ($attribute, $value, $fail) use ($customer) {
                if (!is_string($this->input('first_name'))) {
                    return;
                }
                $key = CustomerIdentity::nameKey($this->input('first_name'), $value);
                if ($customer && $key === CustomerIdentity::nameKey($customer->first_name, $customer->last_name)) {
                    return;
                }
                if ($key && CustomerIdentity::matching('name', $key, $customer)->exists()) {
                    $fail('A customer with this full name already exists. Use the existing customer record.');
                }
            }],
            'address' => ['nullable', 'string'],
            'email' => ['bail', 'nullable', 'string', 'email', 'max:255', function ($attribute, $value, $fail) use ($customer) {
                if ($customer && CustomerIdentity::emailKey($value) === CustomerIdentity::emailKey($customer->email)) {
                    return;
                }
                if (CustomerIdentity::matching('email', CustomerIdentity::emailKey($value), $customer)->exists()) {
                    $fail('This email address is already used by a customer.');
                }
            }],
            'phone_no' => ['bail', 'required', 'string', 'regex:/^[0-9]{7,15}$/D', function ($attribute, $value, $fail) use ($customer) {
                if ($customer && CustomerIdentity::phoneKey($value) === CustomerIdentity::phoneKey($customer->phone_no)) {
                    return;
                }
                if (CustomerIdentity::matching('phone_no', CustomerIdentity::phoneKey($value), $customer)->exists()) {
                    $fail('This phone number is already used by a customer.');
                }
            }],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        if ($customer) {
            $rules['auth_id'] = [
                'bail', 'nullable', 'uuid', Rule::unique('customers', 'auth_id')->ignore($customer->id),
            ];
            if ($customer->auth_id) {
                // Omitted values keep the current link; an established link cannot be replaced.
                $rules['auth_id'][] = function ($attribute, $value, $fail) use ($customer) {
                    if ($value !== $customer->auth_id) {
                        $fail('This customer already has a mobile account. Its account ID cannot be changed.');
                    }
                };
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function ($validator) {
            $customer = $this->route('customer');
            // Nullable rules skip custom validators for an empty input, so guard unlinking here too.
            if ($customer instanceof Customer && $customer->auth_id && $this->exists('auth_id') && !$this->input('auth_id')) {
                $validator->errors()->add('auth_id', 'This customer already has a mobile account. Its account ID cannot be cleared.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'Use letters, spaces, apostrophes, periods, or hyphens for the first name.',
            'last_name.regex' => 'Use letters, spaces, apostrophes, periods, or hyphens for the last name.',
            'phone_no.required' => 'The phone number is required.',
            'phone_no.regex' => 'Enter a valid phone number containing 7 to 15 digits.',
            'auth_id.uuid' => 'The mobile account ID must be a valid UUID from Supabase Auth.',
            'auth_id.unique' => 'This mobile account is already linked to another customer.',
        ];
    }
}
