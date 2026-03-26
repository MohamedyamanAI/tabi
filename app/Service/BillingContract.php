<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;
use Illuminate\Support\Carbon;

/**
 * Default billing implementation when the Billing module is disabled (self-hosted / no Polar).
 *
 * `AppServiceProvider` binds this class only when the Billing module is unavailable or not enabled.
 * When Billing is enabled, the Billing extension replaces this binding with a real implementation
 * that must expose the same public methods.
 *
 * The billing subsystem manages organization subscriptions, trials, seats, and gating.
 */
class BillingContract
{
    /**
     * Check if the organization has a Professional subscription
     * A Professional subscription is a paid subscription that allows the organization to:
     *  - Have more than 1 non-placeholder member
     *  - Access features that are not available to free organizations
     */
    public function hasSubscription(Organization $organization): bool
    {
        return true;
    }

    /**
     * Check if the organization has a trial subscription
     * A trial subscription gives the organization the same benefits as a Professional subscription, but for a limited time
     */
    public function hasTrial(Organization $organization): bool
    {
        return false;
    }

    /**
     * Get the date until which the organization's trial subscription is valid
     * If the organization does not have a trial subscription, this method should return null
     */
    public function getTrialUntil(Organization $organization): ?Carbon
    {
        return null;
    }

    /**
     * Check if the organization is blocked
     * A blocked organization is an organization that has more than 1 non-placeholder member but no subscription/trial
     * This can happen if:
     *  - The organization's trial has expired and during the trial the organization added non-placeholder members
     *  - The organization's subscription has expired and the organization has more than 1 non-placeholder member
     */
    public function isBlocked(Organization $organization): bool
    {
        return false;
    }

    /**
     * Whether the organization can add another real (non-placeholder) member right now.
     * Implementations should enforce the free-plan limit and paid member limits.
     */
    public function canAddMember(Organization $organization): bool
    {
        return true;
    }

    /**
     * Get the tier for the organization (e.g. "track" or "monitor").
     */
    public function getTier(Organization $organization): ?string
    {
        return null;
    }

    /**
     * Total seat count purchased for the current billing period.
     */
    public function getSeatCount(Organization $organization): int
    {
        return 0;
    }

    /**
     * Seats currently used by real (non-placeholder) members.
     */
    public function getUsedSeats(Organization $organization): int
    {
        return 0;
    }

    /**
     * Billing cycle interval for the current subscription (e.g. "monthly" or "annual").
     */
    public function getBillingCycle(Organization $organization): ?string
    {
        return null;
    }

    /**
     * When the current billing period ends.
     */
    public function getCurrentPeriodEnd(Organization $organization): ?Carbon
    {
        return null;
    }
}
