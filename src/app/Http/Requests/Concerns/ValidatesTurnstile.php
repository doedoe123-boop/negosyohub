<?php

namespace App\Http\Requests\Concerns;

use App\Services\TurnstileVerifier;
use Closure;

trait ValidatesTurnstile
{
    /**
     * @return array<int, string>
     */
    protected function turnstileRules(): array
    {
        if (! app(TurnstileVerifier::class)->enabled()) {
            return ['nullable', 'string'];
        }

        return ['required', 'string'];
    }

    /**
     * @return array<int, Closure>
     */
    protected function turnstileValidationHooks(): array
    {
        return [
            function ($validator): void {
                $turnstile = app(TurnstileVerifier::class);

                if (! $turnstile->enabled()) {
                    return;
                }

                if (! $turnstile->verify((string) $this->input('turnstile_token'), $this->ip())) {
                    $validator->errors()->add('turnstile_token', 'Captcha verification failed. Please try again.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function turnstileMessages(): array
    {
        return [
            'turnstile_token.required' => 'Please complete the captcha challenge.',
        ];
    }
}
