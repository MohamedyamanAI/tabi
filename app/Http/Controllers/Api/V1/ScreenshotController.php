<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Screenshot\ScreenshotIndexRequest;
use App\Http\Requests\V1\Screenshot\ScreenshotStoreRequest;
use App\Http\Resources\V1\Screenshot\ScreenshotCollection;
use App\Http\Resources\V1\Screenshot\ScreenshotResource;
use App\Models\Organization;
use App\Models\Screenshot;
use App\Models\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScreenshotController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Screenshot $screenshot = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($screenshot !== null && $screenshot->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Screenshot does not belong to organization');
        }
    }

    /**
     * Get screenshots
     *
     * @return ScreenshotCollection<ScreenshotResource>
     *
     * @operationId getScreenshots
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization, ScreenshotIndexRequest $request): ScreenshotCollection
    {
        $canViewAll = $this->hasPermission($organization, 'screenshots:view:all');
        $canViewOwn = $this->hasPermission($organization, 'screenshots:view:own');

        if (! $canViewAll && ! $canViewOwn) {
            throw new AuthorizationException;
        }

        $query = Screenshot::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('captured_at', 'desc');

        if (! $canViewAll) {
            $member = $this->member($organization);
            $query->where('member_id', $member->getKey());
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->has('time_entry_id')) {
            $query->where('time_entry_id', $request->input('time_entry_id'));
        }

        if ($request->has('start')) {
            $query->where('captured_at', '>=', Carbon::parse($request->input('start')));
        }

        if ($request->has('end')) {
            $query->where('captured_at', '<=', Carbon::parse($request->input('end')));
        }

        return new ScreenshotCollection(
            $query->paginate(config('app.pagination_per_page_default'))
        );
    }

    /**
     * Upload screenshot
     *
     * @operationId createScreenshot
     *
     * @throws AuthorizationException
     */
    public function store(Organization $organization, ScreenshotStoreRequest $request): ScreenshotResource
    {
        $this->checkPermission($organization, 'screenshots:upload');

        if (! $organization->screenshots_enabled) {
            throw new HttpException(422, 'Screenshots are not enabled for this organization');
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

        $file = $request->file('screenshot');
        /** @var \Illuminate\Http\UploadedFile $file */
        $path = $file->store(
            'screenshots/'.$organization->getKey().'/'.$member->getKey(),
            config('filesystems.private')
        );

        if ($path === false) {
            throw new HttpException(500, 'Failed to store screenshot');
        }

        $screenshot = new Screenshot;
        $screenshot->storage_path = $path;
        $screenshot->captured_at = Carbon::parse($request->input('captured_at'));
        $screenshot->timeEntry()->associate($timeEntry);
        $screenshot->member()->associate($member);
        $screenshot->organization()->associate($organization);
        $screenshot->save();

        return new ScreenshotResource($screenshot);
    }

    /**
     * Get screenshot
     *
     * @operationId getScreenshot
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Screenshot $screenshot): ScreenshotResource
    {
        $canViewAll = $this->hasPermission($organization, 'screenshots:view:all');
        $canViewOwn = $this->hasPermission($organization, 'screenshots:view:own');

        if (! $canViewAll && ! $canViewOwn) {
            throw new AuthorizationException;
        }

        if ($screenshot->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Screenshot does not belong to organization');
        }

        if (! $canViewAll) {
            $member = $this->member($organization);
            if ($screenshot->member_id !== $member->getKey()) {
                throw new AuthorizationException;
            }
        }

        return new ScreenshotResource($screenshot);
    }

    /**
     * Delete screenshot
     *
     * @operationId deleteScreenshot
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Screenshot $screenshot): JsonResponse
    {
        if ($screenshot->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Screenshot does not belong to organization');
        }

        $canDeleteAll = $this->hasPermission($organization, 'screenshots:delete:all');
        $canDeleteOwn = $this->hasPermission($organization, 'screenshots:delete:own');

        if (! $canDeleteAll && ! $canDeleteOwn) {
            throw new AuthorizationException;
        }

        if (! $canDeleteAll) {
            $member = $this->member($organization);
            if ($screenshot->member_id !== $member->getKey()) {
                throw new AuthorizationException;
            }
        }

        $screenshot->delete();

        return response()->json(null, 204);
    }
}
