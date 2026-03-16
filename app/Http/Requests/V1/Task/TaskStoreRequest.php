<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Http\Requests\V1\BaseFormRequest;
use App\Enums\TaskStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TaskStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                UniqueEloquent::make(Task::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->where('project_id', '=', $this->input('project_id'));
                })->withCustomTranslation('validation.task_name_already_exists'),
            ],
            'project_id' => [
                'required',
                ExistsEloquent::make(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Estimated time in seconds
            'estimated_time' => [
                'nullable',
                'integer',
                'min:0',
                'max:2147483647',
            ],
            'assignee_id' => [
                'nullable',
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    $projectId = $this->input('project_id');
                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->whereIn('id', ProjectMember::query()->select('member_id')->where('project_id', $projectId));
                })->uuid(),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::enum(TaskStatus::class),
            ],
        ];
    }

    public function getStatus(): TaskStatus
    {
        $value = $this->input('status');

        return ($value !== null && $value !== '') ? TaskStatus::from($value) : TaskStatus::Active;
    }

    public function getAssigneeId(): ?string
    {
        $value = $this->input('assignee_id');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function getEstimatedTime(): ?int
    {
        $input = $this->input('estimated_time');

        return $input !== null && $input !== 0 ? (int) $this->input('estimated_time') : null;
    }
}
