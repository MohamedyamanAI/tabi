<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\ActivitySample\ActivitySampleIndexRequest;
use App\Http\Requests\V1\ActivitySample\ActivitySampleStoreRequest;
use App\Http\Resources\V1\ActivitySample\ActivitySampleCollection;
use App\Models\ActivitySample;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ActivitySampleController extends Controller
{
    /**
     * List activity samples
     *
     * @operationId getActivitySamples
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization, ActivitySampleIndexRequest $request): ActivitySampleCollection
    {
        $canViewAll = $this->hasPermission($organization, 'activity-samples:view:all');
        $canViewOwn = $this->hasPermission($organization, 'activity-samples:view:own');

        if (! $canViewAll && ! $canViewOwn) {
            throw new AuthorizationException;
        }

        $query = ActivitySample::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('timestamp', 'desc');

        if (! $canViewAll) {
            $member = $this->member($organization);
            $query->where('member_id', $member->getKey());
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('time_entry_id')) {
            $query->where('time_entry_id', $request->input('time_entry_id'));
        }

        if ($request->filled('start')) {
            $query->where('timestamp', '>=', Carbon::parse($request->input('start')));
        }

        if ($request->filled('end')) {
            $query->where('timestamp', '<=', Carbon::parse($request->input('end')));
        }

        return new ActivitySampleCollection(
            $query->paginate(config('app.pagination_per_page_default'))
        );
    }

    /**
     * Upload activity samples
     *
     * @operationId createActivitySamples
     *
     * @throws AuthorizationException
     */
    public function store(Organization $organization, ActivitySampleStoreRequest $request): ActivitySampleCollection
    {
        $this->checkPermission($organization, 'activity-samples:upload');

        if (! $organization->activity_tracking_enabled) {
            throw new HttpException(422, 'Activity level tracking is not enabled for this organization');
        }

        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::query()->findOrFail($request->input('time_entry_id'));

        if ($timeEntry->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Time entry does not belong to organization');
        }

        $member = $this->member($organization);

        if ($timeEntry->member_id !== $member->getKey()) {
            throw new AuthorizationException('Time entry does not belong to the authenticated user');
        }

        $saved = [];

        foreach ($request->input('samples') as $row) {
            $timestamp = Carbon::parse($row['timestamp'])->utc();

            $model = ActivitySample::query()->updateOrCreate(
                [
                    'time_entry_id' => $timeEntry->getKey(),
                    'timestamp' => $timestamp,
                ],
                [
                    'keystrokes' => (int) $row['keystrokes'],
                    'mouse_clicks' => (int) $row['mouse_clicks'],
                    'member_id' => $member->getKey(),
                    'organization_id' => $organization->getKey(),
                ]
            );

            $saved[] = $model;
        }

        return new ActivitySampleCollection(collect($saved));
    }
}
