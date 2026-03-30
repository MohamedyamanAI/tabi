<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivitySample;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySample>
 */
class ActivitySampleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'timestamp' => now()->startOfMinute()->utc(),
            'keystrokes' => $this->faker->numberBetween(0, 200),
            'mouse_clicks' => $this->faker->numberBetween(0, 100),
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
