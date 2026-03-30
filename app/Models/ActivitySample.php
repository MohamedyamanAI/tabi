<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\ActivitySampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $timestamp
 * @property int $keystrokes
 * @property int $mouse_clicks
 * @property string $time_entry_id
 * @property string $member_id
 * @property string $organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TimeEntry $timeEntry
 * @property-read Member $member
 * @property-read Organization $organization
 *
 * @method static ActivitySampleFactory factory()
 */
class ActivitySample extends Model
{
    /** @use HasFactory<ActivitySampleFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'timestamp' => 'datetime',
        'keystrokes' => 'integer',
        'mouse_clicks' => 'integer',
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
