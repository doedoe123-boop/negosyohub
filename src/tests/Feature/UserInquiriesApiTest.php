<?php

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\UserRole;

describe('User inquiries API', function () {
    it('lists paginated inquiries for user', function () {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'store_id' => $store->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/inquiries')
            ->assertOk()
            ->assertJsonFragment(['id' => $inquiry->id])
            ->assertJsonFragment(['title' => $property->title])
            ->assertJsonFragment(['name' => $store->name]);
    });

    it('returns empty list for user with no inquiries', function () {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/inquiries')
            ->assertOk()
            ->assertJsonFragment(['data' => []]);
    });

    it('returns 401 for unauthenticated requests', function () {
        $this->getJson('/api/v1/user/inquiries')->assertUnauthorized();
    });
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
