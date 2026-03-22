<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ScreenshotController;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Screenshot;
use App\Models\TimeEntry;
use App\Service\BillingContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ScreenshotController::class)]
class ScreenshotEndpointTest extends ApiEndpointTestAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProBillingTier();
    }

    private function mockProBillingTier(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(true);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('getTier')->andReturn('pro');
            $mock->shouldReceive('getSeatCount')->andReturn(5);
            $mock->shouldReceive('getUsedSeats')->andReturn(1);
            $mock->shouldReceive('getBillingCycle')->andReturn('monthly');
            $mock->shouldReceive('getCurrentPeriodEnd')->andReturn(null);
        });
    }

    private function mockNonProBillingTier(): void
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

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_screenshots(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_screenshots_for_user_with_view_all_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshots = Screenshot::factory()->forTimeEntry($timeEntry)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_returns_only_own_screenshots_for_user_with_view_own_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:own',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $ownScreenshots = Screenshot::factory()->forTimeEntry($timeEntry)->createMany(2);

        // Create screenshot for another member
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        $otherScreenshot = Screenshot::factory()->forTimeEntry($otherTimeEntry)->create();

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filters_by_time_entry_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Screenshot::factory()->forTimeEntry($timeEntry1)->createMany(2);
        Screenshot::factory()->forTimeEntry($timeEntry2)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [
            $data->organization->getKey(),
            'time_entry_id' => $timeEntry1->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filters_by_member_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Screenshot::factory()->forTimeEntry($timeEntry)->createMany(2);

        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        Screenshot::factory()->forTimeEntry($otherTimeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [
            $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_upload_screenshots(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create()->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_screenshots_not_enabled_for_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = false;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertStatus(422);
    }

    public function test_index_endpoint_fails_for_non_pro_tier_even_with_view_permission(): void
    {
        // Arrange
        $this->mockNonProBillingTier();
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_for_non_pro_tier_even_if_enabled_and_with_permission(): void
    {
        // Arrange
        $this->mockNonProBillingTier();
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_for_non_pro_tier_even_with_view_permission(): void
    {
        // Arrange
        $this->mockNonProBillingTier();
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.show', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_for_non_pro_tier_even_with_delete_permission(): void
    {
        // Arrange
        $this->mockNonProBillingTier();
        $data = $this->createUserWithPermission([
            'screenshots:delete:all',
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_time_entry_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $otherTimeEntry->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_time_entry_belongs_to_another_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $otherTimeEntry->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_with_invalid_file_type(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'captured_at' => now()->toIso8601ZuluString(),
            'screenshot' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['screenshot']);
    }

    public function test_store_endpoint_uploads_screenshot_successfully(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $data = $this->createUserWithPermission([
            'screenshots:upload',
        ]);
        $data->organization->screenshots_enabled = true;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $capturedAt = now()->toIso8601ZuluString();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.screenshots.store', [$data->organization->getKey()]), [
            'time_entry_id' => $timeEntry->getKey(),
            'captured_at' => $capturedAt,
            'screenshot' => UploadedFile::fake()->image('screenshot.jpg', 320, 180),
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.time_entry_id', $timeEntry->getKey())
            ->where('data.member_id', $data->member->getKey())
            ->has('data.id')
            ->has('data.captured_at')
            ->has('data.image_url')
        );
        $this->assertDatabaseHas(Screenshot::class, [
            'time_entry_id' => $timeEntry->getKey(),
            'member_id' => $data->member->getKey(),
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_show_endpoint_fails_if_user_has_no_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.show', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_if_screenshot_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($otherOrganization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($otherTimeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.show', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_screenshot(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:view:all',
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.screenshots.show', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.id', $screenshot->getKey())
            ->where('data.time_entry_id', $timeEntry->getKey())
            ->where('data.member_id', $data->member->getKey())
        );
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_screenshots(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_screenshot_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:delete:all',
        ]);
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($otherOrganization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($otherTimeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_deletes_screenshot(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $data = $this->createUserWithPermission([
            'screenshots:delete:all',
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(Screenshot::class, [
            'id' => $screenshot->getKey(),
        ]);
    }

    public function test_destroy_endpoint_allows_user_to_delete_own_screenshot(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $data = $this->createUserWithPermission([
            'screenshots:delete:own',
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($timeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Screenshot::class, [
            'id' => $screenshot->getKey(),
        ]);
    }

    public function test_destroy_endpoint_prevents_user_from_deleting_other_users_screenshot_with_only_own_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'screenshots:delete:own',
        ]);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        $screenshot = Screenshot::factory()->forTimeEntry($otherTimeEntry)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.screenshots.destroy', [$data->organization->getKey(), $screenshot->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Screenshot::class, [
            'id' => $screenshot->getKey(),
        ]);
    }
}
