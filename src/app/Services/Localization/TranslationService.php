<?php

namespace App\Services\Localization;

use App\Models\Translation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Lunar\Models\Language;

class TranslationService
{
    /**
     * @var array<int, string>
     */
    private array $groups = [
        'common',
        'nav',
        'auth',
        'cart',
        'checkout',
        'orders',
        'listings',
    ];

    public function activeLocales(): array
    {
        return Cache::rememberForever('localization.active-locales.catalog', function (): array {
            return Language::query()
                ->where('is_active', true)
                ->orderByDesc('default')
                ->orderBy('name')
                ->get(['name', 'code', 'default'])
                ->map(fn (Language $language): array => [
                    'name' => $language->name,
                    'code' => $language->code,
                    'is_default' => (bool) $language->default,
                ])
                ->all();
        });
    }

    public function defaultLocale(): string
    {
        return Cache::rememberForever('localization.default-locale.catalog', function (): string {
            return Language::query()
                ->where('default', true)
                ->value('code') ?? config('app.locale');
        });
    }

    public function resolveLocale(?string $locale = null): string
    {
        $locale ??= App::currentLocale();
        $activeLocales = collect($this->activeLocales())->pluck('code')->all();

        if (in_array($locale, $activeLocales, true)) {
            return $locale;
        }

        return $this->defaultLocale();
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $resolvedLocale = $this->resolveLocale($locale);
        $override = $this->overridesForLocale($resolvedLocale)[$key] ?? null;

        if ($override !== null) {
            return strtr($override, $this->placeholderReplacements($replace));
        }

        return Lang::get($key, $replace, $resolvedLocale);
    }

    public function catalog(?string $locale = null): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return Cache::remember("localization.catalog.{$resolvedLocale}", now()->addHour(), function () use ($resolvedLocale): array {
            $fallbackLocale = config('app.fallback_locale', 'en');
            $messages = [];

            foreach ($this->groups as $group) {
                $messages[$group] = array_replace_recursive(
                    $this->groupMessages($fallbackLocale, $group),
                    $this->groupMessages($resolvedLocale, $group),
                );
            }

            foreach ($this->overridesForLocale($resolvedLocale) as $key => $value) {
                Arr::set($messages, $key, $value);
            }

            return $messages;
        });
    }

    private function overridesForLocale(string $locale): array
    {
        return Cache::remember("localization.overrides.{$locale}", now()->addHour(), function () use ($locale): array {
            return Translation::query()
                ->where('locale', $locale)
                ->pluck('value', 'key')
                ->all();
        });
    }

    private function groupMessages(string $locale, string $group): array
    {
        $path = lang_path("{$locale}/{$group}.php");

        if (! File::exists($path)) {
            return [];
        }

        $messages = require $path;

        return is_array($messages) ? $messages : [];
    }

    private function placeholderReplacements(array $replace): array
    {
        return collect($replace)
            ->mapWithKeys(fn ($value, $key): array => [':'.$key => (string) $value])
            ->all();
    }
}
