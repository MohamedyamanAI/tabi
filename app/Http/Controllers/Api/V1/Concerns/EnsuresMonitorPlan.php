<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Organization;
use App\Service\BillingContract;
use Illuminate\Auth\Access\AuthorizationException;

trait EnsuresMonitorPlan
{
    /**
     * @throws AuthorizationException
     */
    protected function ensureMonitorPlan(Organization $organization, ?string $message = null): void
    {
        if (app(BillingContract::class)->getTier($organization) !== 'monitor') {
            throw new AuthorizationException($message ?? 'This feature is only available on the Monitor plan.');
        }
    }
}
