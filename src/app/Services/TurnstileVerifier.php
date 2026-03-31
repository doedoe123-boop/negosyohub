<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileVerifier
{
    public function enabled(): bool
    {
        return (bool) config('services.turnstile.enabled')
            && filled(config('services.turnstile.secret_key'));
    }

    public function verify(?string $token, ?string $ipAddress = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            return (bool) $response->json('success', false);
        } catch (Throwable) {
            return false;
        }
    }
}
