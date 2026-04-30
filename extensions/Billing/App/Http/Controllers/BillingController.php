<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Http\Controllers;

use App\Models\Organization;
use App\Service\BillingContract;
use App\Service\PermissionStore;
use Extensions\Billing\App\Services\PolarClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Polar\Models\Components;
use Polar\Models\Errors\APIException;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        private BillingContract $billing,
        private PermissionStore $permissionStore,
        private PolarClient $polarClient,
    ) {}

    private function assertBillingAuthorized(Organization $org): ?JsonResponse
    {
        if (! $this->permissionStore->has($org, 'billing')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return null;
    }

    /**
     * Log billing request failures with enough context for production debugging.
     *
     * @param array<string, mixed> $context
     */
    private function logBillingError(string $action, \Throwable $error, ?Organization $org, int|string|null $userId, array $context = []): void
    {
        $baseContext = [
            'action' => $action,
            'organization_id' => $org?->id,
            'user_id' => $userId,
            'exception' => $error::class,
            'message' => $error->getMessage(),
        ];

        if ($error instanceof APIException) {
            $baseContext['polar_status'] = $error->statusCode;
            $baseContext['polar_body'] = $error->body;
            $baseContext['polar_request_id'] = $error->rawResponse?->getHeaderLine('x-request-id');
        }

        Log::error('Billing request failed', array_merge($baseContext, $context));
    }

    /**
     * GET /billing — Show the billing page.
     */
    public function index(Request $request): \Inertia\Response
    {
        $org = $request->user()->currentOrganization;

        if (! $org instanceof Organization) {
            abort(422, 'No active organization selected');
        }

        if (! $this->permissionStore->has($org, 'billing')) {
            abort(403);
        }

        // Billing depends on membership + Polar relations.
        // In local/dev we have `preventLazyLoading`, so we must eager-load what we use.
        $org->loadMissing([
            'polarSubscription',
        ]);

        return \Inertia\Inertia::render('Billing/Index', [
            'organization' => $org,
            'plans' => config('billing.products'), // Pass plans for selection
        ]);
    }

    /**
     * POST /billing/checkout — Create a Polar checkout session.
     */
    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_key' => 'required|string|in:track_monthly,track_annual,monitor_monthly,monitor_annual',
            'seats' => 'required|integer|min:1',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->currentOrganization;

        if (! $org) {
            return response()->json(['error' => 'No active organization selected'], 422);
        }

        $unauthorized = $this->assertBillingAuthorized($org);
        if ($unauthorized !== null) {
            return $unauthorized;
        }

        $newSeats = (int) $request->input('seats');
        $currentMembers = $this->billing->getUsedSeats($org);
        if ($newSeats < $currentMembers) {
            return response()->json([
                'error' => "Seats must be at least {$currentMembers}.",
            ], 422);
        }

        $productId = config('billing.products.'.$request->input('product_key'));

        if (! $productId) {
            return response()->json(['error' => 'Invalid product configuration'], 422);
        }

        $checkoutCreate = new Components\CheckoutCreate(
            products: [(string) $productId],
            seats: $newSeats,
            successUrl: config('billing.checkout_success_url'),
            externalCustomerId: $org->id,
            customerEmail: $user->email,
            metadata: ['organization_id' => $org->id],
        );

        try {
            $response = $this->polarClient->createCheckout($checkoutCreate);
            $checkout = $response->checkout;

            return response()->json([
                'url' => $checkout->url,
            ]);
        } catch (\Throwable $e) {
            $this->logBillingError('checkout', $e, $org, $user->id, [
                'product_key' => $request->input('product_key'),
                'seats' => $newSeats,
            ]);
            return response()->json(['error' => 'Failed to create checkout: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /billing/seats — Update seat count.
     */
    public function updateSeats(Request $request): JsonResponse
    {
        $request->validate([
            'seats' => 'required|integer|min:1',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->currentOrganization;

        if (! $org) {
            return response()->json(['error' => 'No active organization selected'], 422);
        }

        $unauthorized = $this->assertBillingAuthorized($org);
        if ($unauthorized !== null) {
            return $unauthorized;
        }

        $sub = $org->polarSubscription;

        if (! $sub) {
            return response()->json(['error' => 'No active subscription found'], 422);
        }

        $newSeats = (int) $request->input('seats');
        $currentMembers = $this->billing->getUsedSeats($org);
        $currentPaidSeats = $sub->seats;

        if ($newSeats < $currentMembers) {
            return response()->json([
                'error' => "Cannot reduce seats below current member count ({$currentMembers}).",
            ], 422);
        }

        if ($newSeats < $currentPaidSeats) {
            return response()->json([
                'error' => "Seat count cannot be lowered. Current plan includes {$currentPaidSeats} seat(s); you can only add more.",
            ], 422);
        }

        if ($newSeats === $currentPaidSeats) {
            return response()->json(['seats' => $newSeats]);
        }

        try {
            $this->polarClient->updateSubscription(
                $sub->polar_id,
                new Components\SubscriptionUpdateSeats(seats: $newSeats)
            );

            $sub->update(['seats' => $newSeats]);

            return response()->json(['seats' => $newSeats]);
        } catch (\Throwable $e) {
            $this->logBillingError('update_seats', $e, $org, $user->id, [
                'requested_seats' => $newSeats,
                'current_paid_seats' => $currentPaidSeats,
            ]);
            return response()->json(['error' => 'Failed to update seats: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /billing/swap — Change tier (swap product).
     */
    public function swap(Request $request): JsonResponse
    {
        $request->validate([
            'product_key' => 'required|string|in:track_monthly,track_annual,monitor_monthly,monitor_annual',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->currentOrganization;

        if (! $org) {
            return response()->json(['error' => 'No active organization selected'], 422);
        }

        $unauthorized = $this->assertBillingAuthorized($org);
        if ($unauthorized !== null) {
            return $unauthorized;
        }

        $sub = $org->polarSubscription;

        if (! $sub) {
            return response()->json(['error' => 'No active subscription found for this organization'], 422);
        }

        $newProductId = config('billing.products.'.$request->input('product_key'));

        if (! $newProductId) {
            return response()->json(['error' => 'Invalid product configuration'], 422);
        }

        try {
            $this->polarClient->updateSubscription(
                $sub->polar_id,
                new Components\SubscriptionUpdateProduct(productId: (string) $newProductId)
            );

            $sub->update(['product_id' => $newProductId]);

            return response()->json(['tier' => $this->billing->getTier($org)]);
        } catch (\Throwable $e) {
            $this->logBillingError('swap', $e, $org, $user->id, [
                'product_key' => $request->input('product_key'),
                'subscription_id' => $sub->polar_id,
            ]);
            return response()->json(['error' => 'Failed to swap plan: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /billing/cancel — Cancel subscription at period end.
     */
    public function cancel(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->currentOrganization;

        if (! $org) {
            return response()->json(['error' => 'No active organization selected'], 422);
        }

        $unauthorized = $this->assertBillingAuthorized($org);
        if ($unauthorized !== null) {
            return $unauthorized;
        }

        $sub = $org->polarSubscription;

        if (! $sub) {
            return response()->json(['error' => 'No active subscription found'], 422);
        }

        try {
            $this->polarClient->updateSubscription(
                $sub->polar_id,
                new Components\SubscriptionCancel(cancelAtPeriodEnd: true)
            );

            return response()->json(['status' => 'canceling', 'ends_at' => $sub->current_period_end?->toIso8601ZuluString()]);
        } catch (\Throwable $e) {
            $this->logBillingError('cancel', $e, $org, $user->id, [
                'subscription_id' => $sub->polar_id,
            ]);
            return response()->json(['error' => 'Failed to cancel: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /billing/resume — Resume a canceled subscription (if still in grace period).
     */
    public function resume(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->currentOrganization;

        if (! $org) {
            return response()->json(['error' => 'No active organization selected'], 422);
        }

        $unauthorized = $this->assertBillingAuthorized($org);
        if ($unauthorized !== null) {
            return $unauthorized;
        }

        $sub = $org->polarSubscription;

        if (! $sub) {
            return response()->json(['error' => 'No active subscription found'], 422);
        }

        try {
            $this->polarClient->updateSubscription(
                $sub->polar_id,
                new Components\SubscriptionCancel(cancelAtPeriodEnd: false)
            );

            $sub->update([
                'status' => 'active',
                'cancel_at_period_end' => false,
                'ends_at' => null,
            ]);

            return response()->json(['status' => 'active']);
        } catch (\Throwable $e) {
            $this->logBillingError('resume', $e, $org, $user->id, [
                'subscription_id' => $sub->polar_id,
            ]);
            return response()->json(['error' => 'Failed to resume: '.$e->getMessage()], 500);
        }
    }
}
