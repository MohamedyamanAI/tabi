<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $polar_id
 * @property string $polar_customer_id
 * @property string $product_id
 * @property string $status
 * @property bool $cancel_at_period_end
 * @property int $seats
 * @property string $recurring_interval
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PolarSubscription extends Model
{
    use HasUuids;

    protected $table = 'polar_subscriptions';

    protected $fillable = [
        'organization_id',
        'polar_id',
        'polar_customer_id',
        'product_id',
        'status',
        'cancel_at_period_end',
        'seats',
        'recurring_interval',
        'current_period_end',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'seats' => 'integer',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at?->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }
}
