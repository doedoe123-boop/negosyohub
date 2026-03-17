<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row — always id = 1.
 *
 * @property string $site_name
 * @property string $title_template e.g. "%s | NegosyoHub"
 * @property ?string $default_description
 * @property ?string $default_og_image
 * @property ?string $twitter_site
 * @property string $twitter_card
 * @property ?string $google_analytics_id
 * @property ?string $google_tag_manager_id
 * @property ?string $facebook_pixel_id
 * @property ?string $robots_txt_content
 * @property bool $sitemap_enabled
 */
class GlobalSeoSetting extends Model
{
    protected $table = 'global_seo_settings';

    protected $fillable = [
        'site_name',
        'title_template',
        'default_description',
        'default_og_image',
        'twitter_site',
        'twitter_card',
        'google_analytics_id',
        'google_tag_manager_id',
        'facebook_pixel_id',
        'robots_txt_content',
        'sitemap_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sitemap_enabled' => 'boolean',
        ];
    }

    /**
     * Return the one-and-only settings row, creating it with sensible defaults if missing.
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => config('app.name', 'NegosyoHub'),
                'title_template' => '%s | NegosyoHub',
                'default_description' => 'Find properties, products, and services on NegosyoHub — the Philippines\' multi-sector marketplace.',
                'twitter_card' => 'summary_large_image',
                'sitemap_enabled' => true,
            ]
        );
    }
}
