<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Http\Requests\V1\BaseFormRequest;
use App\Enums\TaskStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Task|null $task Task from model binding
 */
class TaskUpdateRequest extends BaseFormRequest
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
                    return $builder->where('project_id', '=', $this->task->project_id);
                })->ignore($this->task?->getKey())->withCustomTranslation('validation.task_name_already_exists'),
            ],
            'is_done' => [
                'boolean',
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
                    $projectId = $this->task?->project_id;
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

    public function hasStatus(): bool
    {
        return $this->has('status');
    }

    public function getStatus(): TaskStatus
    {
        $value = $this->input('status');

        return ($value !== null && $value !== '') ? TaskStatus::from($value) : TaskStatus::Active;
    }

    public function hasAssigneeId(): bool
    {
        return $this->has('assignee_id');
    }

    public function getAssigneeId(): ?string
    {
        $value = $this->input('assignee_id');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function getIsDone(): bool
    {
        assert($this->has('is_done'));

        return $this->boolean('is_done');
    }

    public function getEstimatedTime(): ?int
    {
        $input = $this->input('estimated_time');

        return $input !== null && $input !== 0 ? (int) $this->input('estimated_time') : null;
    }
}
