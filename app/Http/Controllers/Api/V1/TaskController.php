<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Task\TaskIndexRequest;
use App\Http\Requests\V1\Task\TaskStoreRequest;
use App\Http\Requests\V1\Task\TaskUpdateRequest;
use App\Http\Resources\V1\Task\TaskCollection;
use App\Http\Resources\V1\Task\TaskResource;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Task $task = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($task !== null && $task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }
    }

    /**
     * Check scoped permission and verify user has access to the project
     *
     * @throws AuthorizationException
     */
    private function checkScopedPermissionForProject(Organization $organization, Project $project, string $permission): void
    {
        $this->checkPermission($organization, $permission);

        $user = $this->user();
        $hasAccess = Project::query()
            ->where('id', $project->id)
            ->visibleByEmployee($user)
            ->exists();

        if (! $hasAccess) {
            throw new AuthorizationException('You do not have permission to '.$permission.' in this project.');
        }
    }

    /**
     * Get tasks
     *
     * @return TaskCollection<TaskResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getTasks
     */
    public function index(Organization $organization, TaskIndexRequest $request): TaskCollection
    {
        $this->checkPermission($organization, 'tasks:view');
        $canViewAllTasks = $this->hasPermission($organization, 'tasks:view:all');
        $user = $this->user();

        $projectId = $request->input('project_id');

        $query = Task::query()
            ->whereBelongsTo($organization, 'organization');

        if ($projectId !== null) {
            $query->where('project_id', '=', $projectId);
        }

        if (! $canViewAllTasks) {
            $query->visibleByEmployee($user);
        }
        $doneFilter = $request->getFilterDone();
        if ($doneFilter === 'true') {
            $query->whereNotNull('done_at');
        } elseif ($doneFilter === 'false') {
            $query->whereNull('done_at');
        }

        $tasks = $query
            ->with(['assignee.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_per_page_default'));

        return new TaskCollection($tasks);
    }

    /**
     * Create task
     *
     * @throws AuthorizationException
     *
     * @operationId createTask
     */
    public function store(Organization $organization, TaskStoreRequest $request): JsonResource
    {
        /** @var Project $project */
        $project = Project::query()->findOrFail($request->input('project_id'));

        if ($this->hasPermission($organization, 'tasks:create:all')) {
            $this->checkPermission($organization, 'tasks:create:all');
        } else {
            $this->checkScopedPermissionForProject($organization, $project, 'tasks:create');
        }

        $task = new Task;
        $task->name = $request->input('name');
        $task->project_id = $request->input('project_id');
        $task->assignee_id = $request->getAssigneeId();
        $task->status = $request->has('status') ? $request->getStatus() : TaskStatus::Active;
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        $task->organization()->associate($organization);
        $this->syncDoneAtForStatus($task);
        $task->save();
        $task->load('assignee.user');

        return new TaskResource($task);
    }

    /**
     * Update task
     *
     * @throws AuthorizationException
     *
     * @operationId updateTask
     */
    public function update(Organization $organization, Task $task, TaskUpdateRequest $request): JsonResource
    {
        // Check task belongs to organization
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }

        $member = $this->member($organization);
        $isAssignee = $task->assignee_id !== null && $task->assignee_id === $member->id;
        $canUpdateTasks = $this->hasPermission($organization, 'tasks:update:all')
            || $this->hasPermission($organization, 'tasks:update');

        // Assignee can update status (e.g. mark as done) without full tasks:update permission
        if ($isAssignee && ! $canUpdateTasks) {
            $user = $this->user();
            $hasAccess = Project::query()
                ->where('id', $task->project_id)
                ->visibleByEmployee($user)
                ->exists();
            if (! $hasAccess) {
                throw new AuthorizationException('You do not have access to this project.');
            }
            if ($request->has('status')) {
                $task->status = $request->getStatus();
                $this->syncDoneAtForStatus($task);
            } elseif ($request->has('is_done')) {
                $task->status = $request->getIsDone() ? TaskStatus::Done : TaskStatus::Active;
                $this->syncDoneAtForStatus($task);
            }
            $task->save();
            $task->load('assignee.user');

            return new TaskResource($task);
        }

        if ($canUpdateTasks && $this->hasPermission($organization, 'tasks:update:all')) {
            $this->checkPermission($organization, 'tasks:update:all');
        } elseif ($canUpdateTasks) {
            $this->checkScopedPermissionForProject($organization, $task->project, 'tasks:update');
        } else {
            throw new AuthorizationException;
        }

        $task->name = $request->input('name');
        if ($request->hasAssigneeId()) {
            $task->assignee_id = $request->getAssigneeId();
        }
        if ($request->hasStatus()) {
            $task->status = $request->getStatus();
            $this->syncDoneAtForStatus($task);
        }
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        if ($request->has('is_done') && ! $request->hasStatus()) {
            $task->status = $request->getIsDone() ? TaskStatus::Done : TaskStatus::Active;
            $this->syncDoneAtForStatus($task);
        }
        $task->save();
        $task->load('assignee.user');

        return new TaskResource($task);
    }

    private function syncDoneAtForStatus(Task $task): void
    {
        if ($task->status === TaskStatus::Done) {
            $task->done_at = $task->done_at ?? Carbon::now();
        } else {
            $task->done_at = null;
        }
    }

    /**
     * Delete task
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteTask
     */
    public function destroy(Organization $organization, Task $task): JsonResponse
    {
        // Check task belongs to organization
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }

        if ($this->hasPermission($organization, 'tasks:delete:all')) {
            $this->checkPermission($organization, 'tasks:delete:all');
        } else {
            $this->checkScopedPermissionForProject($organization, $task->project, 'tasks:delete');
        }

        if ($task->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('task', 'time_entry');
        }

        $task->delete();

        return response()
            ->json(null, 204);
    }
}
