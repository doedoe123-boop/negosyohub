<?php

namespace App\Services;

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles customer authentication: registration, login, and token management.
 *
 * All credential verification and user creation logic lives here, keeping
 * controllers responsible only for HTTP request/response handling.
 */
class AuthService
{
    /**
     * Register a new customer, persist them, and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     */
    public function register(string $name, string $email, string $password, string $deviceName): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Customer,
        ]);

        $token = $user->createToken($deviceName)->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Verify credentials and issue a Sanctum token for an existing user.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, string $deviceName): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Revoke the user's current Sanctum access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
