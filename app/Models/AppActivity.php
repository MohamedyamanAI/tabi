<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\AppActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $timestamp
 * @property string $app_name
 * @property string $window_title
 * @property string|null $url
 * @property int $duration_seconds
 * @property string $time_entry_id
 * @property string $member_id
 * @property string $organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TimeEntry $timeEntry
 * @property-read Member $member
 * @property-read Organization $organization
 *
 * @method static AppActivityFactory factory()
 */
class AppActivity extends Model
{
    /** @use HasFactory<AppActivityFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'timestamp' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    /**
     * @return BelongsTo<TimeEntry, $this>
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class, 'time_entry_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
