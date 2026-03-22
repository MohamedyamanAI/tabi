<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Services;

use App\Models\Organization;
use App\Service\BillingContract;
use Extensions\Billing\App\Models\PolarSubscription;
use Illuminate\Support\Carbon;

class PolarBillingService extends BillingContract
{
    public function hasSubscription(Organization $organization): bool
    {
        $sub = $this->getSubscription($organization);

        return $sub !== null && in_array($sub->status, ['active', 'trialing']);
    }

    public function hasTrial(Organization $organization): bool
    {
        $sub = $this->getSubscription($organization);

        return $sub?->status === 'trialing'
            && $sub->trial_ends_at?->isFuture();
    }

    public function getTrialUntil(Organization $organization): ?Carbon
    {
        return $this->getSubscription($organization)?->trial_ends_at;
    }

    public function isBlocked(Organization $organization): bool
    {
        $realMembers = $organization->allRealUsers()->count();
        $sub = $this->getSubscription($organization);

        // No subscription and multiple members → blocked
        if (! $this->hasSubscription($organization) && $realMembers > 1) {
            return true;
        }

        // Has subscription but over seat limit → blocked
        if ($sub !== null && $realMembers > $sub->seats) {
            return true;
        }

        return false;
    }

    public function getTier(Organization $organization): ?string
    {
        $productId = $this->getSubscription($organization)?->product_id;
        if ($productId === null) {
            return null;
        }

        foreach (config('billing.tiers') as $tier => $productIds) {
            if (in_array($productId, $productIds, true)) {
                return $tier;
            }
        }

        return null;
    }

    public function getSeatCount(Organization $organization): int
    {
        return $this->getSubscription($organization)?->seats ?? 0;
    }

    public function getUsedSeats(Organization $organization): int
    {
        return $organization->allRealUsers()->count();
    }

    public function canAddMember(Organization $organization): bool
    {
        if (! $this->hasSubscription($organization)) {
            return $this->getUsedSeats($organization) < 1; // free tier: 1 member max
        }

        return $this->getUsedSeats($organization) < $this->getSeatCount($organization);
    }

    public function hasScreenshots(Organization $organization): bool
    {
        return $this->getTier($organization) === 'pro';
    }

    public function getBillingCycle(Organization $organization): ?string
    {
        $interval = $this->getSubscription($organization)?->recurring_interval;
        if ($interval === null) {
            return null;
        }

        return $interval === 'year' ? 'annual' : 'monthly';
    }

    public function getCurrentPeriodEnd(Organization $organization): ?Carbon
    {
        return $this->getSubscription($organization)?->current_period_end;
    }

    /**
     * Get the cached subscription for the organization.
     */
    private function getSubscription(Organization $organization): ?PolarSubscription
    {
        return $organization->polarSubscription;
    }
}
