<?php

namespace App\Services\Products;

final class ProductBillingRules
{
    public const ONE_TIME = 'one_time';

    public const MONTHLY = 'monthly';

    /** @return array<int, string> */
    public static function supported(): array
    {
        return [self::ONE_TIME, self::MONTHLY];
    }

    /** @return array<int, string> */
    public static function allowedFor(string $productType): array
    {
        return match ($productType) {
            'course', 'course_package' => [self::ONE_TIME],
            'consulting', 'service' => [self::ONE_TIME, self::MONTHLY],
            default => [],
        };
    }

    public static function allows(string $productType, string $billingType): bool
    {
        return in_array($billingType, self::allowedFor($productType), true);
    }

    public static function isFixedOneTime(string $productType): bool
    {
        return in_array($productType, ['course', 'course_package'], true);
    }

    public static function validationMessage(string $productType): string
    {
        return self::isFixedOneTime($productType)
            ? 'Courses and course packages must use one-time billing.'
            : 'Services and consulting products must use one-time or monthly billing.';
    }

    public static function label(string $billingType): string
    {
        return match ($billingType) {
            self::ONE_TIME => 'One-time',
            self::MONTHLY => 'Monthly',
            default => 'Unsupported',
        };
    }

    public static function priceLabel(?float $price, string $currency, string $billingType): string
    {
        if ($price === null) {
            return 'Price not set';
        }

        return $currency.' '.number_format($price, 2)
            .($billingType === self::MONTHLY ? ' / month' : '');
    }
}
