<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Http\Controllers;

use App\Models\Organization;
use Extensions\Billing\App\Models\PolarSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('Polar webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        $event = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('Polar webhook received', ['event' => $event]);

        return match ($event) {
            'subscription.created' => $this->handleSubscriptionCreated($data),
            'subscription.updated' => $this->handleSubscriptionUpdated($data),
            'subscription.canceled' => $this->handleSubscriptionCanceled($data),
            'subscription.revoked' => $this->handleSubscriptionRevoked($data),
            default => response()->json(['status' => 'ignored']),
        };
    }

    private function handleSubscriptionCreated(array $data): JsonResponse
    {
        $externalCustomerId = $data['metadata']['organization_id']
            ?? $data['customer']['external_id']
            ?? null;

        if (! $externalCustomerId) {
            Log::warning('Polar subscription.created missing organization mapping', $data);

            return response()->json(['error' => 'No organization mapping'], 422);
        }

        $org = Organization::find($externalCustomerId);
        if (! $org) {
            Log::warning('Organization not found for Polar subscription', ['id' => $externalCustomerId]);

            return response()->json(['error' => 'Organization not found'], 404);
        }

        // Set Polar customer ID on org
        $org->update(['polar_customer_id' => $data['customer_id'] ?? $data['customer']['id'] ?? null]);

        PolarSubscription::updateOrCreate(
            ['polar_id' => $data['id']],
            [
                'organization_id' => $org->id,
                'polar_customer_id' => $data['customer_id'] ?? $data['customer']['id'] ?? '',
                'product_id' => $data['product_id'] ?? $data['product']['id'] ?? '',
                'status' => $data['status'] ?? 'active',
                'cancel_at_period_end' => $data['cancel_at_period_end'] ?? false,
                'seats' => $data['seats'] ?? 1,
                'recurring_interval' => $data['recurring_interval'] ?? 'month',
                'current_period_end' => $data['current_period_end'] ?? null,
                'trial_ends_at' => $data['trial_end'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]
        );

        Log::info('Polar subscription created', ['polar_id' => $data['id'], 'org' => $org->id]);

        return response()->json(['status' => 'ok']);
    }

    private function handleSubscriptionUpdated(array $data): JsonResponse
    {
        $sub = PolarSubscription::where('polar_id', $data['id'])->first();

        if (! $sub) {
            Log::warning('Polar subscription.updated for unknown subscription', ['polar_id' => $data['id']]);

            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $sub->update([
            'product_id' => $data['product_id'] ?? $data['product']['id'] ?? $sub->product_id,
            'status' => $data['status'] ?? $sub->status,
            'cancel_at_period_end' => $data['cancel_at_period_end'] ?? $sub->cancel_at_period_end,
            'seats' => $data['seats'] ?? $sub->seats,
            'recurring_interval' => $data['recurring_interval'] ?? $sub->recurring_interval,
            'current_period_end' => $data['current_period_end'] ?? $sub->current_period_end,
            'trial_ends_at' => $data['trial_end'] ?? $sub->trial_ends_at,
            'ends_at' => $data['ends_at'] ?? $sub->ends_at,
        ]);

        Log::info('Polar subscription updated', ['polar_id' => $data['id']]);

        return response()->json(['status' => 'ok']);
    }

    private function handleSubscriptionCanceled(array $data): JsonResponse
    {
        $sub = PolarSubscription::where('polar_id', $data['id'])->first();

        if (! $sub) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $update = [
            'status' => $data['status'] ?? 'canceled',
            'cancel_at_period_end' => $data['cancel_at_period_end'] ?? $sub->cancel_at_period_end,
            'current_period_end' => $data['current_period_end'] ?? $sub->current_period_end,
        ];
        if (array_key_exists('ends_at', $data)) {
            $update['ends_at'] = $data['ends_at'];
        }

        $sub->update($update);

        Log::info('Polar subscription canceled', ['polar_id' => $data['id']]);

        return response()->json(['status' => 'ok']);
    }

    private function handleSubscriptionRevoked(array $data): JsonResponse
    {
        PolarSubscription::where('polar_id', $data['id'])->delete();

        Log::info('Polar subscription revoked (deleted)', ['polar_id' => $data['id']]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify Polar webhook signature using standard webhook secret (HMAC-SHA256).
     */
    
     private function verifySignature(Request $request): bool
     {
         $secret = config('billing.polar_webhook_secret'); // = "polar_whs_xxxx"
     
         if (empty($secret)) {
             return ! app()->environment('production');
         }
     
         try {
             // Polar gives you the raw secret (polar_whs_...).
             // Standard Webhooks library needs: whsec_ + base64(rawSecret)
             $normalizedSecret = 'whsec_' . base64_encode($secret);
     
             $wh = new \StandardWebhooks\Webhook($normalizedSecret);
     
             $wh->verify($request->getContent(), [
                 'webhook-id'        => $request->header('webhook-id'),
                 'webhook-timestamp' => $request->header('webhook-timestamp'),
                 'webhook-signature' => $request->header('webhook-signature'),
             ]);
     
             return true;
         } catch (\StandardWebhooks\WebhookVerificationError $e) {
             Log::warning('Polar webhook signature verification failed', [
                 'error' => $e->getMessage(),
             ]);
     
             return false;
         }
     }
}
