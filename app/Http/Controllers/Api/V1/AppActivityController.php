<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\AppActivity\AppActivityIndexRequest;
use App\Http\Requests\V1\AppActivity\AppActivityStoreRequest;
use App\Http\Requests\V1\AppActivity\AppActivitySummaryRequest;
use App\Http\Resources\V1\AppActivity\AppActivityCollection;
use App\Models\AppActivity;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppActivityController extends Controller
{
    /**
     * List app activities
     *
     * @operationId getAppActivities
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization, AppActivityIndexRequest $request): AppActivityCollection
    {
        $canViewAll = $this->hasPermission($organization, 'app-activities:view:all');
        $canViewOwn = $this->hasPermission($organization, 'app-activities:view:own');

        if (! $canViewAll && ! $canViewOwn) {
            throw new AuthorizationException;
        }

        $query = AppActivity::query()
            ->whereBelongsTo($organization, 'organization');

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

        if ($request->filled('app_name')) {
            $query->where('app_name', $request->input('app_name'));
        }

        $sort = $request->input('sort', 'timestamp');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $column = match ($sort) {
            'app_name' => 'app_name',
            'window_title' => 'window_title',
            'duration_seconds' => 'duration_seconds',
            default => 'timestamp',
        };

        $query->orderBy($column, $direction);

        $perPage = (int) $request->input('per_page', config('app.pagination_per_page_default'));
        $perPage = max(1, min(100, $perPage));

        return new AppActivityCollection(
            $query->paginate($perPage)
        );
    }

    /**
     * Aggregated time per application
     *
     * @operationId getAppActivitiesSummary
     *
     * @throws AuthorizationException
     */
    public function summary(Organization $organization, AppActivitySummaryRequest $request): JsonResponse
    {
        $canViewAll = $this->hasPermission($organization, 'app-activities:view:all');
        $canViewOwn = $this->hasPermission($organization, 'app-activities:view:own');

        if (! $canViewAll && ! $canViewOwn) {
            throw new AuthorizationException;
        }

        $query = AppActivity::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBetween('timestamp', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end')),
            ]);

        if ($request->filled('time_entry_id')) {
            $timeEntry = TimeEntry::query()
                ->whereBelongsTo($organization, 'organization')
                ->findOrFail($request->input('time_entry_id'));

            if (! $canViewAll) {
                $member = $this->member($organization);
                if ($timeEntry->member_id !== $member->getKey()) {
                    throw new AuthorizationException;
                }
            }

            $query->where('time_entry_id', $timeEntry->getKey());
        }

        if (! $canViewAll) {
            $member = $this->member($organization);
            $query->where('member_id', $member->getKey());
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        $rows = $query
            ->select([
                'app_name',
                DB::raw('SUM(duration_seconds) as total_seconds'),
                DB::raw('COUNT(*) as session_count'),
            ])
            ->groupBy('app_name')
            ->orderByDesc('total_seconds')
            ->get();

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'app_name' => $row->app_name,
                'total_seconds' => (int) $row->total_seconds,
                'session_count' => (int) $row->session_count,
            ]),
        ]);
    }

    /**
     * Upload app activities
     *
     * @operationId createAppActivities
     *
     * @throws AuthorizationException
     */
    public function store(Organization $organization, AppActivityStoreRequest $request): AppActivityCollection
    {
        $this->checkPermission($organization, 'app-activities:upload');

        if (! $organization->activity_tracking_enabled) {
            throw new HttpException(422, 'Activity level tracking is not enabled for this organization');
        }

        if (! $organization->app_activity_sync_enabled) {
            throw new HttpException(422, 'App activity sync is not enabled for this organization');
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

        foreach ($request->input('activities') as $row) {
            $timestamp = Carbon::parse($row['timestamp'])->utc();

            $model = AppActivity::query()->updateOrCreate(
                [
                    'time_entry_id' => $timeEntry->getKey(),
                    'timestamp' => $timestamp,
                    'app_name' => $row['app_name'],
                ],
                [
                    'window_title' => $row['window_title'],
                    'url' => $row['url'] ?? null,
                    'duration_seconds' => (int) $row['duration_seconds'],
                    'member_id' => $member->getKey(),
                    'organization_id' => $organization->getKey(),
                ]
            );

            $saved[] = $model;
        }

        return new AppActivityCollection(collect($saved));
    }
}
