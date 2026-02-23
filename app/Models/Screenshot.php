<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\ScreenshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $storage_path
 * @property Carbon $captured_at
 * @property string $time_entry_id
 * @property string $member_id
 * @property string $organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TimeEntry $timeEntry
 * @property-read Member $member
 * @property-read Organization $organization
 *
 * @method static ScreenshotFactory factory()
 */
class Screenshot extends Model
{
    /** @use HasFactory<ScreenshotFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'captured_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Screenshot $screenshot): void {
            $disk = Storage::disk(config('filesystems.private'));
            if ($disk->exists($screenshot->storage_path)) {
                $disk->delete($screenshot->storage_path);
            }
        });
    }

    public function getTemporaryUrl(): string
    {
        return Storage::disk(config('filesystems.private'))
            ->temporaryUrl($this->storage_path, now()->addMinutes(15));
    }

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
