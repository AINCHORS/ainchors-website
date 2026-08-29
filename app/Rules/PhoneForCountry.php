<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneForCountry implements ValidationRule
{
    public function __construct(private readonly string $country) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = preg_replace('/[\s().-]+/', '', (string) $value);
        $patterns = [
            'Australia' => '/^(?:\+?61|0)[2-478]\d{8}$/',
            'Canada' => '/^(?:\+?1)?[2-9]\d{9}$/',
            'China' => '/^(?:\+?86)?1[3-9]\d{9}$/',
            'Hong Kong' => '/^(?:\+?852)?[2-9]\d{7}$/',
            'Japan' => '/^(?:\+?81|0)(?:[789]0\d{8}|[1-9]\d{8,9})$/',
            'Malaysia' => '/^(?:\+?60|0)1[0-46-9]\d{7,8}$/',
            'New Zealand' => '/^(?:\+?64|0)(?:2\d{7,9}|[3-9]\d{7})$/',
            'Singapore' => '/^(?:\+?65)?[3689]\d{7}$/',
            'United Kingdom' => '/^(?:\+?44|0)\d{9,10}$/',
            'United States' => '/^(?:\+?1)?[2-9]\d{9}$/',
        ];
        $pattern = $patterns[$this->country] ?? '/^\+?[1-9]\d{7,14}$/';

        if (! preg_match($pattern, $phone)) {
            $fail("Please enter a valid phone number for {$this->country}, including the country code when applicable.");
        }
    }
}
