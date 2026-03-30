<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ActivitySampleController;
use App\Models\ActivitySample;
use App\Models\Member;
use App\Models\TimeEntry;
use App\Service\BillingContract;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ActivitySampleController::class)]
class ActivitySampleEndpointTest extends ApiEndpointTestAbstract
{
    private function mockMonitorTier(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(true);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('getTier')->andReturn('monitor');
            $mock->shouldReceive('getSeatCount')->andReturn(5);
            $mock->shouldReceive('getUsedSeats')->andReturn(1);
            $mock->shouldReceive('getBillingCycle')->andReturn('monthly');
            $mock->shouldReceive('getCurrentPeriodEnd')->andReturn(null);
        });
    }

    private function mockStandardTier(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(true);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('getTier')->andReturn('standard');
            $mock->shouldReceive('getSeatCount')->andReturn(5);
            $mock->shouldReceive('getUsedSeats')->andReturn(1);
            $mock->shouldReceive('getBillingCycle')->andReturn('monthly');
            $mock->shouldReceive('getCurrentPeriodEnd')->andReturn(null);
        });
    }

    public function test_index_forbidden_without_permission(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.activity-samples.index', [$data->organization->getKey()]));

        $response->assertForbidden();
    }

    public function test_index_returns_samples_with_view_all(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['activity-samples:view:all']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        ActivitySample::factory()->forTimeEntry($timeEntry)->create(['timestamp' => now()->subMinutes(10)->startOfMinute()->utc()]);
        ActivitySample::factory()->forTimeEntry($timeEntry)->create(['timestamp' => now()->subMinutes(11)->startOfMinute()->utc()]);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.activity-samples.index', [$data->organization->getKey()]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_scoped_to_own_for_view_own(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['activity-samples:view:own']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        ActivitySample::factory()->forTimeEntry($timeEntry)->create(['timestamp' => now()->subMinutes(10)->startOfMinute()->utc()]);
        ActivitySample::factory()->forTimeEntry($timeEntry)->create(['timestamp' => now()->subMinutes(11)->startOfMinute()->utc()]);

        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        ActivitySample::factory()->forTimeEntry($otherEntry)->create(['timestamp' => now()->subMinutes(20)->startOfMinute()->utc()]);

        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.activity-samples.index', [$data->organization->getKey()]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_ok_on_standard_tier(): void
    {
        $this->mockStandardTier();
        $data = $this->createUserWithPermission(['activity-samples:view:all']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.activity-samples.index', [$data->organization->getKey()]));

        $response->assertOk();
    }

    public function test_store_requires_upload_permission(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission();
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.activity-samples.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'samples' => [
                [
                    'timestamp' => now()->startOfMinute()->toIso8601ZuluString(),
                    'keystrokes' => 10,
                    'mouse_clicks' => 2,
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_store_fails_when_activity_tracking_disabled(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['activity-samples:upload']);
        $data->organization->activity_tracking_enabled = false;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.activity-samples.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'samples' => [
                [
                    'timestamp' => now()->startOfMinute()->toIso8601ZuluString(),
                    'keystrokes' => 10,
                    'mouse_clicks' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_store_creates_samples(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['activity-samples:upload']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        $ts = now()->startOfMinute()->utc();

        $response = $this->postJson(route('api.v1.activity-samples.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'samples' => [
                [
                    'timestamp' => $ts->toIso8601ZuluString(),
                    'keystrokes' => 10,
                    'mouse_clicks' => 2,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertDatabaseCount('activity_samples', 1);
    }

    public function test_store_upserts_same_timestamp(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['activity-samples:upload']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        $ts = now()->startOfMinute()->utc();

        $payload = [
            'time_entry_id' => $timeEntry->getKey(),
            'samples' => [
                [
                    'timestamp' => $ts->toIso8601ZuluString(),
                    'keystrokes' => 10,
                    'mouse_clicks' => 2,
                ],
            ],
        ];

        $this->postJson(route('api.v1.activity-samples.store', [$data->organization->getKey()]), $payload)->assertOk();
        $this->postJson(route('api.v1.activity-samples.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'samples' => [
                [
                    'timestamp' => $ts->toIso8601ZuluString(),
                    'keystrokes' => 99,
                    'mouse_clicks' => 1,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('activity_samples', 1);
        $this->assertSame(99, ActivitySample::query()->first()?->keystrokes);
    }
}
