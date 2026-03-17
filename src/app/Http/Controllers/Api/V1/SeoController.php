<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GlobalSeoSetting;
use Illuminate\Http\JsonResponse;

class SeoController extends Controller
{
    /**
     * Return global SEO & analytics settings for the Vue SPA.
     *
     * Only fields safe for public consumption are returned.
     * Custom head scripts are intentionally excluded to prevent XSS via a
     * compromised API response — use Google Tag Manager for custom scripts.
     */
    public function global(): JsonResponse
    {
        $settings = GlobalSeoSetting::current();

        return response()->json([
            'site_name' => $settings->site_name,
            'title_template' => $settings->title_template,
            'default_description' => $settings->default_description,
            'default_og_image' => $settings->default_og_image,
            'twitter_site' => $settings->twitter_site,
            'twitter_card' => $settings->twitter_card,
            'google_analytics_id' => $settings->google_analytics_id,
            'google_tag_manager_id' => $settings->google_tag_manager_id,
            'facebook_pixel_id' => $settings->facebook_pixel_id,
        ]);
    }
}
