<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerIdentity
{
    public static function cleanName(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value ?? ''));

        return $value === '' ? null : $value;
    }

    public static function nameKey(?string $firstName, ?string $lastName): ?string
    {
        $firstName = self::cleanName($firstName);
        $lastName = self::cleanName($lastName);

        return $firstName && $lastName ? mb_strtolower($firstName.' '.$lastName) : null;
    }

    public static function emailKey(?string $value): ?string
    {
        $value = mb_strtolower(trim($value ?? ''));

        return $value === '' ? null : $value;
    }

    public static function phoneKey(?string $value): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $value ?? '');
        if (preg_match('/^09[0-9]{9}$/', $digits)) {
            $digits = '63'.substr($digits, 1);
        } elseif (preg_match('/^9[0-9]{9}$/', $digits)) {
            $digits = '63'.$digits;
        } elseif (preg_match('/^0063[0-9]{10}$/', $digits)) {
            $digits = substr($digits, 2);
        }

        return $digits === '' ? null : $digits;
    }

    /** Match the database's unique indexes, including archived customers. */
    public static function matching(string $field, string $key, ?Customer $except = null): Builder
    {
        $expression = match ($field) {
            'name' => 'customer_identity_name_key(first_name, last_name)',
            'email' => 'customer_identity_email_key(email)',
            'phone_no' => 'customer_identity_phone_key(phone_no)',
        };

        return Customer::withTrashed()
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->whereRaw($expression.' = ?', [$key]);
    }
}
