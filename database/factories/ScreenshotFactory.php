<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Screenshot;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Screenshot>
 */
class ScreenshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storage_path' => 'screenshots/'.$this->faker->uuid().'/'.$this->faker->uuid().'.jpg',
            'captured_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
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

    public function forOrganization(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization): array {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function forMember(Member $member): self
    {
        return $this->state(function (array $attributes) use ($member): array {
            return [
                'member_id' => $member->getKey(),
                'organization_id' => $member->organization_id,
            ];
        });
    }
}
