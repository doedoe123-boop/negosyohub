<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\BrevoNewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterSubscriptionController extends Controller
{
    public function __invoke(
        Request $request,
        BrevoNewsletterService $brevoNewsletterService
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'redirect_anchor' => ['nullable', 'string', 'max:100'],
        ]);

        $subscriber = NewsletterSubscriber::query()->firstOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'source' => $validated['source'] ?? 'website',
                'subscribed_at' => now(),
            ],
        );

        $brevoNewsletterService->subscribe(
            $subscriber->email,
            $validated['source'] ?? $subscriber->source,
        );

        $redirectUrl = url()->previous();

        if (filled($validated['redirect_anchor'] ?? null)) {
            $redirectUrl .= '#'.ltrim($validated['redirect_anchor'], '#');
        }

        return redirect($redirectUrl)
            ->with('newsletter_status', 'Thanks. You are now subscribed for marketplace updates and deal alerts.');
    }
}
