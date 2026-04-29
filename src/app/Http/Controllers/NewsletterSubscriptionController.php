<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\BrevoNewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NewsletterSubscriptionController extends Controller
{
    public function __invoke(
        Request $request,
        BrevoNewsletterService $brevoNewsletterService
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('newsletter_subscribers', 'email'),
            ],
            'source' => ['nullable', 'string', 'max:100'],
            'redirect_anchor' => ['nullable', 'string', 'max:100'],
        ], [
            'email.unique' => 'This email is already subscribed to marketplace updates.',
        ]);

        if ($validator->fails()) {
            $redirectUrl = url()->previous();
            $redirectAnchor = $request->input('redirect_anchor');

            if (filled($redirectAnchor)) {
                $redirectUrl .= '#'.ltrim((string) $redirectAnchor, '#');
            }

            return redirect($redirectUrl)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => $validated['email'],
            'source' => $validated['source'] ?? 'website',
            'subscribed_at' => now(),
        ]);

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
