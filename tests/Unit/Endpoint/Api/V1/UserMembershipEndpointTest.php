<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Member;
use App\Models\Organization;
use Laravel\Passport\Passport;

class UserMembershipEndpointTest extends ApiEndpointTestAbstract
{
    public function test_my_members_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.users.memberships.my-memberships'));

        // Assert
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_my_members_returns_information_about_the_organization_membership_of_the_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.memberships.my-memberships'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $otherMemberResponse = collect($response->json('data'))->where('id', '=', $otherMember->getKey())->first();
        $this->assertNotNull($otherMemberResponse);
        $this->assertSame($otherMember->organization->getKey(), $otherMemberResponse['organization']['id']);
        $this->assertArrayHasKey('tier', $otherMemberResponse['organization']);
        $this->assertIsBool($otherMemberResponse['organization']['is_blocked']);
        $this->assertIsArray($otherMemberResponse['organization']['entitlements']);
        $this->assertArrayHasKey('screenshots', $otherMemberResponse['organization']['entitlements']);
        $this->assertIsBool($otherMemberResponse['organization']['entitlements']['screenshots']);
        $memberResponse = collect($response->json('data'))->where('id', '=', $data->member->getKey())->first();
        $this->assertNotNull($memberResponse);
        $this->assertArrayHasKey('tier', $memberResponse['organization']);
        $this->assertIsBool($memberResponse['organization']['is_blocked']);
        $this->assertIsArray($memberResponse['organization']['entitlements']);
        $this->assertArrayHasKey('screenshots', $memberResponse['organization']['entitlements']);
        $this->assertIsBool($memberResponse['organization']['entitlements']['screenshots']);
    }
}
