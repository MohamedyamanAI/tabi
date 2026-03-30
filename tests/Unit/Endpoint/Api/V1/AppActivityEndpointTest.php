<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\AppActivityController;
use App\Models\AppActivity;
use App\Models\Member;
use App\Models\TimeEntry;
use App\Service\BillingContract;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(AppActivityController::class)]
class AppActivityEndpointTest extends ApiEndpointTestAbstract
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

    public function test_summary_forbidden_without_permission(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $start = now()->subDay()->toIso8601ZuluString();
        $end = now()->toIso8601ZuluString();

        $response = $this->getJson(route('api.v1.app-activities.summary', [
            $data->organization->getKey(),
            'start' => $start,
            'end' => $end,
        ]));

        $response->assertForbidden();
    }

    public function test_store_fails_when_app_sync_disabled(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:upload']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->app_activity_sync_enabled = false;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.app-activities.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'activities' => [
                [
                    'timestamp' => now()->toIso8601ZuluString(),
                    'app_name' => 'Chrome',
                    'window_title' => 'Test',
                    'url' => null,
                    'duration_seconds' => 60,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_store_creates_activities(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:upload']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->app_activity_sync_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        $ts = now()->startOfMinute()->utc();

        $response = $this->postJson(route('api.v1.app-activities.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'activities' => [
                [
                    'timestamp' => $ts->toIso8601ZuluString(),
                    'app_name' => 'Chrome',
                    'window_title' => 'Test',
                    'url' => 'https://example.com/path',
                    'duration_seconds' => 60,
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('app_activities', 1);
    }

    public function test_summary_groups_by_app(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:view:all']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $t1 = now()->subHours(2)->startOfMinute()->utc();
        $t2 = now()->subHour()->startOfMinute()->utc();
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => $t1,
            'app_name' => 'Chrome',
            'duration_seconds' => 120,
        ]);
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => $t2,
            'app_name' => 'Chrome',
            'duration_seconds' => 60,
        ]);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.app-activities.summary', [
            $data->organization->getKey(),
            'start' => $t1->copy()->subMinute()->toIso8601ZuluString(),
            'end' => now()->toIso8601ZuluString(),
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.0.app_name', 'Chrome');
        $response->assertJsonPath('data.0.total_seconds', 180);
        $response->assertJsonPath('data.0.session_count', 2);
    }

    public function test_summary_filters_by_time_entry_id(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:view:all']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntryA = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $timeEntryB = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $t1 = now()->subHours(3)->startOfMinute()->utc();
        AppActivity::factory()->forTimeEntry($timeEntryA)->create([
            'timestamp' => $t1,
            'app_name' => 'Chrome',
            'duration_seconds' => 100,
        ]);
        AppActivity::factory()->forTimeEntry($timeEntryB)->create([
            'timestamp' => $t1->copy()->addMinute(),
            'app_name' => 'Slack',
            'duration_seconds' => 200,
        ]);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.app-activities.summary', [
            $data->organization->getKey(),
            'start' => $t1->copy()->subHour()->toIso8601ZuluString(),
            'end' => now()->toIso8601ZuluString(),
            'time_entry_id' => $timeEntryA->getKey(),
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.app_name', 'Chrome');
        $response->assertJsonPath('data.0.total_seconds', 100);
    }

    public function test_index_ok_on_standard_tier(): void
    {
        $this->mockStandardTier();
        $data = $this->createUserWithPermission(['app-activities:view:all']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.app-activities.index', [$data->organization->getKey()]));

        $response->assertOk();
    }

    public function test_index_scoped_to_own(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:view:own']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => now()->subMinutes(30)->startOfMinute()->utc(),
            'app_name' => 'A',
        ]);
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => now()->subMinutes(31)->startOfMinute()->utc(),
            'app_name' => 'B',
        ]);

        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        AppActivity::factory()->forTimeEntry($otherEntry)->create();

        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.app-activities.index', [$data->organization->getKey()]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_sorts_by_app_name_ascending(): void
    {
        $this->mockMonitorTier();
        $data = $this->createUserWithPermission(['app-activities:view:all']);
        $data->organization->activity_tracking_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => now()->subMinutes(30)->startOfMinute()->utc(),
            'app_name' => 'Zed',
        ]);
        AppActivity::factory()->forTimeEntry($timeEntry)->create([
            'timestamp' => now()->subMinutes(31)->startOfMinute()->utc(),
            'app_name' => 'Alpha',
        ]);

        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.app-activities.index', [
            $data->organization->getKey(),
            'sort' => 'app_name',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.0.app_name', 'Alpha');
        $response->assertJsonPath('data.1.app_name', 'Zed');
    }
}
