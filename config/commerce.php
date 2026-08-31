<?php

return [
    'supported_currencies' => [
        'AUD' => 'Australian Dollar (AUD)',
        'USD' => 'US Dollar (USD)',
        'MYR' => 'Malaysian Ringgit (MYR)',
        'CNY' => 'Chinese Yuan (CNY)',
        'SGD' => 'Singapore Dollar (SGD)',
        'EUR' => 'Euro (EUR)',
        'GBP' => 'British Pound (GBP)',
        'JPY' => 'Japanese Yen (JPY)',
        'HKD' => 'Hong Kong Dollar (HKD)',
        'NZD' => 'New Zealand Dollar (NZD)',
        'CAD' => 'Canadian Dollar (CAD)',
    ],

    'payment' => [
        // Demo remains the safe local default. Set the driver to "hosted"
        // only after the selected providers have sandbox credentials.
        'driver' => env('PAYMENT_DRIVER', 'demo'),
        'environment' => env('PAYMENT_ENVIRONMENT', 'sandbox'),
        'enabled_providers' => array_values(array_filter(array_map(
            static fn (string $provider): string => strtolower(trim($provider)),
            explode(',', (string) env('PAYMENT_PROVIDERS', 'stripe,paypal')),
        ))),
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'api_url' => env('STRIPE_API_URL', 'https://api.stripe.com'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'sandbox_url' => env('PAYPAL_SANDBOX_URL', 'https://api-m.sandbox.paypal.com'),
            'live_url' => env('PAYPAL_LIVE_URL', 'https://api-m.paypal.com'),
        ],
    ],

    'invoices' => [
        // Only provider-hosted financial documents are allowed. AINCHORS does
        // not generate or host its own invoices/receipts in this checkout flow.
        'provider_hosts' => [
            'stripe' => array_values(array_filter(array_map(
                static fn (string $host): string => strtolower(trim($host)),
                explode(',', (string) env('STRIPE_INVOICE_HOSTS', 'invoice.stripe.com')),
            ))),
        ],
    ],
];
