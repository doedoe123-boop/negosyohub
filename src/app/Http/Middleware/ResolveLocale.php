<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Lunar\Models\Language;
use Symfony\Component\HttpFoundation\Response;

class ResolveLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->headers->set('Vary', 'Accept-Language, X-Locale');

        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        $defaultLocale = Cache::rememberForever('localization.default-locale', function (): string {
            return Language::query()
                ->where('default', true)
                ->value('code') ?? config('app.locale');
        });

        $activeLocales = Cache::rememberForever('localization.active-locales', function (): array {
            return Language::query()
                ->where('is_active', true)
                ->pluck('code')
                ->all();
        });

        $candidates = array_filter([
            $request->header('X-Locale'),
            $request->query('locale'),
            $request->user()?->preferred_locale,
            $request->hasSession() ? $request->session()->get('locale') : null,
            $request->getPreferredLanguage($activeLocales ?: [$defaultLocale]),
        ]);

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $activeLocales, true)) {
                return $candidate;
            }
        }

        return $defaultLocale;
    }
}
