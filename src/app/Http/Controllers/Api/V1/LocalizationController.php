<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Localization\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    public function __invoke(Request $request, TranslationService $translationService): JsonResponse
    {
        $requestedLocale = $request->query('locale');
        $resolvedLocale = $translationService->resolveLocale($requestedLocale);

        return response()->json([
            'locale' => $resolvedLocale,
            'fallback_locale' => $translationService->defaultLocale(),
            'available_locales' => $translationService->activeLocales(),
            'messages' => $translationService->catalog($resolvedLocale),
        ]);
    }
}
