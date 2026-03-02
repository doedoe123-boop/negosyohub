<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;

// Strong password satisfying the API registration policy:
// min 8 chars, uppercase + lowercase + number + symbol.
const VALID_PASSWORD = 'Ngh0syHub#2024!';

// --- Registration ---

it('registers a new customer via API', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])
        ->assertStatus(201)
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.name', 'John Doe')
        ->assertJsonPath('user.email', 'john@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'role'  => UserRole::Customer->value,
    ]);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'Jane Doe',
        'email'                 => 'taken@example.com',
        'password'              => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects registration with missing required fields', function () {
    $this->postJson(route('api.v1.auth.register'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects registration with a weak password', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'Weak Pass User',
        'email'                 => 'weak@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('rejects registration when passwords do not match', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'Mismatch User',
        'email'                 => 'mismatch@example.com',
        'password'              => VALID_PASSWORD,
        'password_confirmation' => 'DifferentP@ss1!',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('accepts an optional device_name during registration', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'Device User',
        'email'                 => 'device@example.com',
        'password'              => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
        'device_name'           => 'iPhone 15',
    ])->assertStatus(201);
});

// --- Login ---

it('logs in a user via API and returns a token', function () {
    User::factory()->create([
        'email'    => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'login@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', 'login@example.com');
});

it('rejects login with invalid credentials', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'user@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects login with missing required fields', function () {
    $this->postJson(route('api.v1.auth.login'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

// --- Authenticated Endpoints ---

it('returns the authenticated user', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.auth.user'))
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('logs out and revokes the token', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.auth.logout'))
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    // Clear cached Sanctum guard state before re-using the revoked token
    auth('sanctum')->forgetUser();

    $this->withToken($token)
        ->getJson(route('api.v1.auth.user'))
        ->assertUnauthorized();
});

it('rejects unauthenticated access to protected endpoints', function () {
    $this->getJson(route('api.v1.auth.user'))->assertUnauthorized();
    $this->postJson(route('api.v1.auth.logout'))->assertUnauthorized();
});

// --- Role Assignment ---

it('always assigns the customer role on API registration', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name'                  => 'New Customer',
        'email'                 => 'customer@example.com',
        'password'              => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])->assertStatus(201);

    $user = User::where('email', 'customer@example.com')->first();
    expect($user->role)->toBe(UserRole::Customer);
});
