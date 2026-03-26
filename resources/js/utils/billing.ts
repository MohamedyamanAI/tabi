import { usePage } from '@inertiajs/vue3';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';

export function isBillingActivated() {
    const page = usePage<{
        has_billing_extension: boolean;
    }>();

    return page.props.has_billing_extension;
}

export function isInvoicingActivated() {
    const page = usePage<{
        has_invoicing_extension: boolean;
    }>();

    return page.props.has_invoicing_extension;
}

export function isInTrial() {
    const page = usePage<{
        billing: {
            has_trial: boolean;
        };
    }>();

    return page.props.billing.has_trial;
}

export function daysLeftInTrial() {
    const page = usePage<{
        billing: {
            trial_until: string;
        };
    }>();

    return (
        getDayJsInstance()(page.props.billing.trial_until).diff(getDayJsInstance()(), 'days') + 1
    );
}

export function isBlocked() {
    const page = usePage<{
        billing: {
            is_blocked: boolean;
        };
    }>();

    return page.props.billing.is_blocked;
}

export function getTier() {
    const page = usePage<{
        billing: {
            tier: 'track' | 'monitor' | null;
        };
    }>();

    return page.props.billing.tier;
}

export function getSeatCount() {
    const page = usePage<{
        billing: {
            seat_count: number;
        };
    }>();

    return page.props.billing.seat_count;
}

export function getUsedSeats() {
    const page = usePage<{
        billing: {
            used_seats: number;
        };
    }>();

    return page.props.billing.used_seats;
}

export function getBillingCycle() {
    const page = usePage<{
        billing: {
            billing_cycle: 'monthly' | 'annual' | null;
        };
    }>();

    return page.props.billing.billing_cycle;
}

export function getCurrentPeriodEndsAt() {
    const page = usePage<{
        billing: {
            current_period_ends_at: string | null;
        };
    }>();

    return page.props.billing.current_period_ends_at;
}

export function isAllowedToUseScreenshots() {
    return getTier() === 'monitor';
}

export function isFreePlan() {
    return !hasActiveSubscription() && !isInTrial();
}

export function hasActiveSubscription() {
    const page = usePage<{
        billing: {
            has_subscription: boolean;
        };
    }>();

    return page.props.billing.has_subscription;
}

export function isAllowedToPerformPremiumAction() {
    return (
        !isBillingActivated() ||
        (isBillingActivated() && hasActiveSubscription()) ||
        (isBillingActivated() && isInTrial())
    );
}
