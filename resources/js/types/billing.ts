export interface BillingState {
    has_subscription: boolean;
    has_trial: boolean;
    trial_until: string | null;
    is_blocked: boolean;
    tier: 'standard' | 'pro' | null;
    seat_count: number;
    used_seats: number;
    billing_cycle: 'monthly' | 'annual' | null;
    current_period_ends_at: string | null;
    /** True when the subscription is scheduled to end at period end (Polar keeps status active until then). */
    cancel_at_period_end: boolean;
}
