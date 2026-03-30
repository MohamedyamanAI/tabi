<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppActivity;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppActivity>
 */
class AppActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'timestamp' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'app_name' => $this->faker->randomElement(['Google Chrome', 'VS Code', 'Slack']),
            'window_title' => $this->faker->sentence(4),
            'url' => $this->faker->optional()->url(),
            'duration_seconds' => $this->faker->numberBetween(30, 3600),
            'time_entry_id' => TimeEntry::factory(),
            'member_id' => Member::factory(),
            'organization_id' => Organization::factory(),
        ];
    }

    public function forTimeEntry(TimeEntry $timeEntry): self
    {
        return $this->state(function (array $attributes) use ($timeEntry): array {
            return [
                'time_entry_id' => $timeEntry->getKey(),
                'member_id' => $timeEntry->member_id,
                'organization_id' => $timeEntry->organization_id,
            ];
        });
    }
}
