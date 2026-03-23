<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

/**
 * Downloads sample images from Unsplash and attaches them to MediaLibrary models.
 * Images are cached to storage/app/seeder-images/ to enable idempotent seeding.
 *
 * Uses curated Unsplash photo IDs (licensed under Unsplash License — free for
 * commercial and non-commercial use without attribution required).
 */
class MediaSeederHelper
{
    /**
     * Curated Unsplash photo IDs grouped by subject keyword.
     * IDs sourced from public Unsplash library (unsplash.com/license).
     *
     * @var array<string, list<string>>
     */
    private const PHOTO_IDS = [
        // Real Estate — Houses
        'house' => [
            'photo-1570129477492-45c003edd2be',
            'photo-1564013799919-ab600027ffc6',
            'photo-1568605114967-8130f3a36994',
            'photo-1512917774080-9991f1c4c750',
            'photo-1523217582562-09d0def993a6',
        ],
        // Real Estate — Condos / Apartments
        'condo' => [
            'photo-1545324418-cc1a3fa10c00',
            'photo-1502672260266-1c1ef2d93688',
            'photo-1493809842364-78817add7ffb',
            'photo-1522708323590-d24dbb6b0267',
            'photo-1536376072261-38c75010e6c9',
        ],
        // Real Estate — Commercial
        'commercial' => [
            'photo-1497366754035-f200968a6e72',
            'photo-1497366811353-6870744d04b2',
            'photo-1486406146926-c627a92ad1ab',
            'photo-1560179707-f14e90ef3623',
            'photo-1504307651254-35680f356dfd',
        ],
        // Real Estate — Lots / Land
        'lot' => [
            'photo-1500382017468-9049fed747ef',
            'photo-1441974231531-c6227db76b6e',
            'photo-1518005020951-eccb494ad742',
            'photo-1463123081488-789f998ac9c4',
            'photo-1592595896551-12b371d546d5',
        ],
        // Real Estate — Warehouse / Industrial
        'warehouse' => [
            'photo-1586528116311-ad8dd3c8310d',
            'photo-1553413077-190dd305871c',
            'photo-1601584115197-04ecc0da31d7',
            'photo-1544928147-79a2dbc1f389',
            'photo-1565793298595-6a879b1d9492',
        ],
        // E-commerce — Electronics
        'electronics' => [
            'photo-1498049794561-7780e7231661',
            'photo-1542751371-adc38448a05e',
            'photo-1505740420928-5e560c06d30e',
            'photo-1526738549149-8e07eca6c147',
            'photo-1593640408182-31c228099552',
        ],
        // E-commerce — Clothing / Fashion
        'clothing' => [
            'photo-1523381210434-271e8be1f52b',
            'photo-1489987707025-afc232f7ea0f',
            'photo-1558618666-fcd25c85cd64',
            'photo-1516762689617-e1cffcef479d',
            'photo-1467043237213-65f2da53396f',
        ],
        // E-commerce — Home & Living
        'home_living' => [
            'photo-1555041469-a586c61ea9bc',
            'photo-1538688525198-9b88f6f53126',
            'photo-1493663284031-b7e3aefcae8e',
            'photo-1616486338812-3dadae4b4ace',
            'photo-1540518614846-7eded433c457',
        ],
        // E-commerce — Food & Grocery
        'food' => [
            'photo-1542838132-92c53300491e',
            'photo-1567306226416-28f0efdc88ce',
            'photo-1490645935967-10de6ba17061',
            'photo-1506354666786-959d6d497f1a',
            'photo-1565895405138-6c3a1555da6a',
        ],
        // E-commerce — Health & Beauty
        'health_beauty' => [
            'photo-1556228578-8c89e6adf883',
            'photo-1571781926291-c477ebfd024b',
            'photo-1522338242992-e1a54906a8da',
            'photo-1512290923902-8a9f81dc236c',
            'photo-1526045612212-70caf35c14df',
        ],
        // E-commerce — Sports & Fitness
        'sports' => [
            'photo-1517836357463-d25dfeac3438',
            'photo-1571019613454-1cb2f99b2d8b',
            'photo-1526506118085-60ce8714f8c5',
            'photo-1534258936925-c58bed479fcb',
            'photo-1540497077202-7c8a3999166f',
        ],
        // E-commerce — Books & Stationery
        'books' => [
            'photo-1495446815901-a7297e633e8d',
            'photo-1507842217343-583bb7270b66',
            'photo-1524995997946-a1c2e315a42f',
            'photo-1533669955142-6a73332af4db',
            'photo-1456513080510-7bf3a84b82f8',
        ],
        // E-commerce — General / Other
        'general' => [
            'photo-1472851294608-062f824d29cc',
            'photo-1601924994987-69e26d50dc26',
            'photo-1441986300917-64674bd600d8',
            'photo-1607082348824-0a96f2a4b9da',
            'photo-1555529669-e69e7aa0ba9a',
        ],
        // Movers / Lipat Bahay
        'movers' => [
            'photo-1558618047-3c8c76ca7d13',
            'photo-1600518464441-9154a4dea21b',
            'photo-1586528116493-a029325540fa',
            'photo-1562259949-e8e7689d7828',
            'photo-1631679706909-1844bbd07221',
        ],
    ];

    /**
     * Product type → image keyword map.
     *
     * @var array<string, string>
     */
    private const TYPE_KEYWORD_MAP = [
        'Electronics' => 'electronics',
        'Clothing' => 'clothing',
        'Home & Living' => 'home_living',
        'Food & Grocery' => 'food',
        'Health & Beauty' => 'health_beauty',
        'Sports & Fitness' => 'sports',
        'Books & Stationery' => 'books',
        'Toys & Hobbies' => 'general',
        'Automotive' => 'general',
        'Pets & Accessories' => 'general',
        'Beverage' => 'food',
    ];

    /**
     * Property type → image keyword map.
     *
     * @var array<string, string>
     */
    private const PROP_KEYWORD_MAP = [
        'house' => 'house',
        'condo' => 'condo',
        'apartment' => 'condo',
        'commercial' => 'commercial',
        'land' => 'lot',
        'lot' => 'lot',
        'warehouse' => 'warehouse',
        'office' => 'commercial',
        'villa' => 'house',
        'townhouse' => 'house',
        'room' => 'condo',
    ];

    /**
     * Attach images to a model from Unsplash using a keyword.
     * Downloads and caches images locally — skips download if already cached.
     *
     * @param  HasMedia  $model  The model implementing HasMedia.
     * @param  string  $keyword  Keyword to select the image set (e.g. 'house', 'electronics').
     * @param  string  $collection  MediaLibrary collection name.
     * @param  int  $count  Number of images to attach (1–5).
     */
    public static function attachImages(
        HasMedia $model,
        string $keyword,
        string $collection = 'images',
        int $count = 3,
    ): void {
        if ($model->getMedia($collection)->count() >= $count) {
            return;
        }

        $ids = self::PHOTO_IDS[$keyword] ?? self::PHOTO_IDS['general'];
        $ids = array_slice($ids, 0, min($count, count($ids)));

        foreach ($ids as $i => $photoId) {
            $cachedPath = self::resolveSeedImagePath($keyword, $photoId, $i + 1);
            $absolutePath = Storage::disk('local')->path($cachedPath);

            if (! file_exists($absolutePath)) {
                $cachedPath = self::ensurePlaceholder($keyword, $i + 1);
                $absolutePath = Storage::disk('local')->path($cachedPath);
            }

            $model->addMedia($absolutePath)
                ->preservingOriginal()
                ->usingFileName("{$keyword}-".($i + 1).'.'.pathinfo($absolutePath, PATHINFO_EXTENSION))
                ->toMediaCollection($collection);
        }
    }

    /**
     * Resolve the image keyword for a product type name.
     */
    public static function keywordForProductType(string $typeName): string
    {
        return self::TYPE_KEYWORD_MAP[$typeName] ?? 'general';
    }

    /**
     * Resolve the image keyword for a property type value.
     */
    public static function keywordForPropertyType(string $propertyType): string
    {
        return self::PROP_KEYWORD_MAP[strtolower($propertyType)] ?? 'house';
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    /**
     * Return the local cache path (relative to the local disk root).
     */
    private static function cachedImagePath(string $photoId): string
    {
        return 'seeder-images/'.str_replace('/', '_', $photoId).'.jpg';
    }

    /**
     * Download an Unsplash photo to the local cache.
     * Uses the Unsplash CDN with w=900 for a reasonable file size.
     */
    private static function downloadToCache(string $photoId, string $relativePath): void
    {
        $url = "https://images.unsplash.com/{$photoId}?w=900&q=80&auto=format&fit=crop";

        try {
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                Storage::disk('local')->put($relativePath, $response->body());
            }
        } catch (\Throwable) {
            // Silently skip — seeder continues without the image.
        }
    }

    private static function resolveSeedImagePath(string $keyword, string $photoId, int $index): string
    {
        if (app()->environment('testing')) {
            return self::ensurePlaceholder($keyword, $index);
        }

        $cachedPath = self::cachedImagePath($photoId);

        if (! Storage::disk('local')->exists($cachedPath)) {
            self::downloadToCache($photoId, $cachedPath);
        }

        if (! Storage::disk('local')->exists($cachedPath)) {
            return self::ensurePlaceholder($keyword, $index);
        }

        return $cachedPath;
    }

    private static function ensurePlaceholder(string $keyword, int $index): string
    {
        $relativePath = 'seeder-images/placeholders/'.$keyword.'-'.$index.'.svg';

        if (! Storage::disk('local')->exists($relativePath)) {
            $palette = self::placeholderPalette($keyword);
            $label = str($keyword)->replace('_', ' ')->title()->value();
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="{$label}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$palette['start']}" />
      <stop offset="100%" stop-color="{$palette['end']}" />
    </linearGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)" rx="36" />
  <circle cx="1040" cy="160" r="120" fill="rgba(255,255,255,0.08)" />
  <circle cx="190" cy="660" r="180" fill="rgba(255,255,255,0.06)" />
  <text x="80" y="360" font-family="Arial, Helvetica, sans-serif" font-size="86" font-weight="700" fill="#ffffff">{$label}</text>
  <text x="82" y="435" font-family="Arial, Helvetica, sans-serif" font-size="28" fill="rgba(255,255,255,0.82)">NegosyoHub demo media</text>
  <text x="82" y="485" font-family="Arial, Helvetica, sans-serif" font-size="24" fill="rgba(255,255,255,0.72)">Image {$index}</text>
</svg>
SVG;

            Storage::disk('local')->put($relativePath, $svg);
        }

        return $relativePath;
    }

    /**
     * @return array{start: string, end: string}
     */
    private static function placeholderPalette(string $keyword): array
    {
        return match ($keyword) {
            'electronics' => ['start' => '#102446', 'end' => '#2563eb'],
            'clothing' => ['start' => '#4b1d36', 'end' => '#db2777'],
            'home_living' => ['start' => '#164e3f', 'end' => '#0f766e'],
            'food' => ['start' => '#92400e', 'end' => '#f97316'],
            'health_beauty' => ['start' => '#7c2d12', 'end' => '#fb7185'],
            'sports' => ['start' => '#14532d', 'end' => '#22c55e'],
            'books' => ['start' => '#312e81', 'end' => '#6366f1'],
            'condo', 'commercial' => ['start' => '#111827', 'end' => '#334155'],
            'lot', 'warehouse' => ['start' => '#365314', 'end' => '#65a30d'],
            'movers' => ['start' => '#4c1d95', 'end' => '#7c3aed'],
            default => ['start' => '#0f172a', 'end' => '#1d4ed8'],
        };
    }
}
