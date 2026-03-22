<?php

declare(strict_types=1);

return [
    // Polar server environment: "production" or "sandbox"
    'polar_server' => env('POLAR_SERVER', 'production'),

    // Polar API credentials
    'polar_access_token' => env('POLAR_ACCESS_TOKEN'),
    'polar_webhook_secret' => env('POLAR_WEBHOOK_SECRET'),

    // Product IDs configured in the Polar dashboard.
    // Keys are used by the billing UI/backend.
    'products' => [
        'standard_monthly' => env('POLAR_PRODUCT_STANDARD_MONTHLY'),
        'standard_annual' => env('POLAR_PRODUCT_STANDARD_ANNUAL'),
        'pro_monthly' => env('POLAR_PRODUCT_PRO_MONTHLY'),
        'pro_annual' => env('POLAR_PRODUCT_PRO_ANNUAL'),
    ],

    // Tier mapping derived from product IDs above.
    // Used by PolarBillingService::getTier().
    'tiers' => [
        'standard' => [
            env('POLAR_PRODUCT_STANDARD_MONTHLY'),
            env('POLAR_PRODUCT_STANDARD_ANNUAL'),
        ],
        'pro' => [
            env('POLAR_PRODUCT_PRO_MONTHLY'),
            env('POLAR_PRODUCT_PRO_ANNUAL'),
        ],
    ],

    // Checkout URLs.
    // BillingController uses `checkout_success_url`.
    'checkout_success_url' => rtrim(config('app.url'), '/').'/billing?checkout=success',
    'checkout_return_url' => rtrim(config('app.url'), '/').'/billing',

    // Trial configuration (kept here for UI/commands; Polar trial behavior is controlled by Polar/product config).
    'trial_days' => (int) env('POLAR_TRIAL_DAYS', 7),
];
