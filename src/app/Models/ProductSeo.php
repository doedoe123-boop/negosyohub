<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores SEO overrides for Lunar products.
 *
 * If a field is null, the frontend falls back to the product's name / description.
 *
 * @property int $product_id
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?string $seo_keywords
 * @property ?string $og_image
 */
class ProductSeo extends Model
{
    protected $table = 'product_seo';

    protected $fillable = [
        'product_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
    ];
}
