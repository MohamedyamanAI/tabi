<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'project_id' => Project::factory(),
            'organization_id' => Organization::factory(),
            'status' => TaskStatus::Active,
            'done_at' => null,
            'estimated_time' => null,
        ];
    }

    public function forProject(Project $project): self
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->getKey(),
        ]);
    }

    public function isDone(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
            'done_at' => $this->faker->dateTime('now', 'UTC'),
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }
}
