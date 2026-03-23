<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use App\Http\Resources\V1\BaseResource;
use App\Models\Member;
use App\Service\BillingContract;
use Illuminate\Http\Request;

/**
 * @property Member $resource
 */
class PersonalMembershipResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        $organization = $this->resource->organization;
        /** @var BillingContract $billing */
        $billing = app(BillingContract::class);
        $tier = $billing->getTier($organization);

        return [
            /** @var string $id ID of membership */
            'id' => $this->resource->id,
            'organization' => [
                /** @var string $id ID of organization */
                'id' => $organization->id,
                /** @var string $name Name of organization */
                'name' => $organization->name,
                /** @var string $currency Currency code (ISO 4217) of organization */
                'currency' => $organization->currency,
                /** @var string|null $tier Billing tier for organization */
                'tier' => $tier,
                /** @var bool $is_blocked Whether organization is blocked by billing limits */
                'is_blocked' => $billing->isBlocked($organization),
                /** @var bool $can_add_member Whether organization can add another real member */
                'can_add_member' => $billing->canAddMember($organization),
                'entitlements' => [
                    /** @var bool $screenshots Whether screenshots entitlement is available */
                    'screenshots' => $tier === 'pro',
                ],
            ],
            /** @var string $role Role */
            'role' => $this->resource->role,
        ];
    }
}
