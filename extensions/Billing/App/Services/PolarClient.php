<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Services;

use Polar\Polar;
use Polar\Models\Components;

/**
 * Thin wrapper around the Polar SDK.
 * Centralizes SDK instantiation and provides typed methods for Tabi's billing operations.
 */
class PolarClient
{
    private Polar $polar;

    public function __construct()
    {
        /** @var string $server */
        $server = config('billing.polar_server', 'production');

        $this->polar = Polar::builder()
            ->setServer($server)
            ->setSecurity(config('billing.polar_access_token'))
            ->build();
    }

    public function getSdk(): Polar
    {
        return $this->polar;
    }

    /**
     * Create a checkout session.
     */
    public function createCheckout(Components\CheckoutCreate $checkout): \Polar\Models\Operations\CheckoutsCreateResponse
    {
        return $this->polar->checkouts->create($checkout);
    }

    /**
     * Update a subscription (change product, seats, cancel, etc.)
     */
    public function updateSubscription(
        string $subscriptionId,
        Components\SubscriptionUpdateProduct|Components\SubscriptionUpdateSeats|Components\SubscriptionCancel|Components\SubscriptionRevoke $update
    ): \Polar\Models\Operations\SubscriptionsUpdateResponse {
        return $this->polar->subscriptions->update($update, $subscriptionId);
    }

    /**
     * Get a subscription by ID.
     */
    public function getSubscription(string $subscriptionId): \Polar\Models\Operations\SubscriptionsGetResponse
    {
        return $this->polar->subscriptions->get($subscriptionId);
    }
}
