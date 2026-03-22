<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Providers;

use App\Service\BillingContract;
use Extensions\Billing\App\Services\PolarBillingService;
use Extensions\Billing\App\Services\PolarClient;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override the BillingContract with our Polar implementation
        $this->app->singleton(BillingContract::class, PolarBillingService::class);

        // Register PolarClient as a singleton
        $this->app->singleton(PolarClient::class);


    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/webhooks.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
