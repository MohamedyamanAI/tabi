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
        'track_monthly' => env('POLAR_PRODUCT_TRACK_MONTHLY'),
        'track_annual' => env('POLAR_PRODUCT_TRACK_ANNUAL'),
        'monitor_monthly' => env('POLAR_PRODUCT_MONITOR_MONTHLY'),
        'monitor_annual' => env('POLAR_PRODUCT_MONITOR_ANNUAL'),
    ],

    // Tier mapping derived from product IDs above.
    // Used by PolarBillingService::getTier().
    'tiers' => [
        'track' => [
            env('POLAR_PRODUCT_TRACK_MONTHLY'),
            env('POLAR_PRODUCT_TRACK_ANNUAL'),
        ],
        'monitor' => [
            env('POLAR_PRODUCT_MONITOR_MONTHLY'),
            env('POLAR_PRODUCT_MONITOR_ANNUAL'),
        ],
    ],

    // Checkout URLs.
    // BillingController uses `checkout_success_url`.
    'checkout_success_url' => rtrim(config('app.url'), '/').'/billing?checkout=success',
    'checkout_return_url' => rtrim(config('app.url'), '/').'/billing',

    // Trial configuration (kept here for UI/commands; Polar trial behavior is controlled by Polar/product config).
    'trial_days' => (int) env('POLAR_TRIAL_DAYS', 7),
];
