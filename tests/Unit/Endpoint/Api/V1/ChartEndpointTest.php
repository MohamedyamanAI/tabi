<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Service\BillingContract;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use Tests\Unit\Endpoint\Web\EndpointTestAbstract;

class ChartEndpointTest extends EndpointTestAbstract
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

    public function test_weekly_project_overview_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.weekly-project-overview', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_weekly_project_overview_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.weekly-project-overview', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_latest_tasks_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.latest-tasks', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_latest_tasks_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.latest-tasks', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_last_seven_days_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.last-seven-days', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_last_seven_days_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.last-seven-days', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_latest_team_activity_endpoint_fails_if_user_has_no_permission_to_view_chart_for_the_whole_orgnaization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.latest-team-activity', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_latest_team_activity_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:all']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.latest-team-activity', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_daily_tracked_hours_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.daily-tracked-hours', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_daily_tracked_hours_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.daily-tracked-hours', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_total_weekly_time_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-time', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_total_weekly_time_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-time', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_total_weekly_billable_time_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-time', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_total_weekly_billable_time_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-time', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_total_weekly_billable_amount_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-amount', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_total_weekly_billable_amount_endpoint_fails_if_the_user_is_an_employee_but_the_organization_does_not_allow_employees_to_view_billable_rates(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Employee);
        $organization = $user->organization;
        $organization->employees_can_see_billable_rates = false;
        $organization->save();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-amount', [
            'organization' => $organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_total_weekly_billable_amount_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Employee);
        $organization = $user->organization;
        $organization->employees_can_see_billable_rates = true;
        $organization->save();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-amount', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_weekly_history_endpoint_fails_if_user_has_no_permission_to_view_chart(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.weekly-history', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_weekly_history_endpoint_returns_chart_data(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.weekly-history', [
            'organization' => $user->organization,
        ]));

        // Assert
        $response->assertOk();
    }

    public function test_activity_level_ok_on_standard_tier(): void
    {
        $this->mockStandardTier();
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.activity-level', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }

    public function test_activity_level_returns_data_on_monitor_tier(): void
    {
        $this->mockMonitorTier();
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.activity-level', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }

    public function test_daily_activity_levels_ok_on_standard_tier(): void
    {
        $this->mockStandardTier();
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.daily-activity-levels', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }

    public function test_daily_activity_levels_returns_data_on_monitor_tier(): void
    {
        $this->mockMonitorTier();
        $user = $this->createUserWithPermission(['charts:view:own']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.daily-activity-levels', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }

    public function test_team_activity_levels_ok_on_standard_tier(): void
    {
        $this->mockStandardTier();
        $user = $this->createUserWithPermission(['charts:view:all']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.team-activity-levels', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }

    public function test_team_activity_levels_returns_data_on_monitor_tier(): void
    {
        $this->mockMonitorTier();
        $user = $this->createUserWithPermission(['charts:view:all']);
        Passport::actingAs($user->user);

        $response = $this->getJson(route('api.v1.charts.team-activity-levels', [
            'organization' => $user->organization,
        ]));

        $response->assertOk();
    }
}
